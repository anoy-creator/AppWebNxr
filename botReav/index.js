require("dotenv").config();

const fs = require("fs");
const cron = require("node-cron");
const {
  Client,
  GatewayIntentBits,
  Partials,
  EmbedBuilder,
  ActionRowBuilder,
  ButtonBuilder,
  ButtonStyle
} = require("discord.js");

const client = new Client({
  intents: [
    GatewayIntentBits.Guilds,
    GatewayIntentBits.GuildMembers
  ],
  partials: [Partials.Channel]
});

const DB_FILE = "./tournois.json";
const STATE_FILE = "./roster-state.json";
const PROPOSITIONS_FILE = "./propositions-tournois.json";

function formatDateFR(timestamp) {
  return new Date(timestamp).toLocaleString("fr-FR", {
    timeZone: "Europe/Paris",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit"
  });
}

function formatShortDate(timestamp) {
  return new Date(timestamp).toLocaleDateString("fr-FR", {
    timeZone: "Europe/Paris",
    weekday: "long"
  });
}

function loadJson(file, fallback) {
  try {
    if (!fs.existsSync(file)) {
      fs.writeFileSync(file, JSON.stringify(fallback, null, 2), "utf8");
      return fallback;
    }

    const content = fs.readFileSync(file, "utf8")
        .replace(/^\uFEFF/, "")
        .replace(/\0/g, "")
        .trim();

    if (!content) {
      fs.writeFileSync(file, JSON.stringify(fallback, null, 2), "utf8");
      return fallback;
    }

    return JSON.parse(content);
  } catch {
    fs.writeFileSync(file, JSON.stringify(fallback, null, 2), "utf8");
    return fallback;
  }
}

function saveJson(file, data) {
  fs.writeFileSync(file, JSON.stringify(data, null, 2), "utf8");
}

function loadTournois() {
  return loadJson(DB_FILE, []);
}

function saveTournois(data) {
  saveJson(DB_FILE, data);
}

function loadState() {
  return loadJson(STATE_FILE, { rosterMessageId: null });
}

function saveState(data) {
  saveJson(STATE_FILE, data);
}

function loadPropositions() {
  return loadJson(PROPOSITIONS_FILE, []);
}

function savePropositions(data) {
  saveJson(PROPOSITIONS_FILE, data);
}

function parseDateTime(date, heure) {
  const [day, month, year] = date.split("/").map(Number);
  const [hours, minutes] = heure.split(":").map(Number);

  if (!day || !month || !year || hours === undefined || minutes === undefined) {
    return new Date("invalid");
  }

  return new Date(year, month - 1, day, hours, minutes, 0);
}

function validateDateHeure(date, heure) {
  const regexDate = /^([0-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/\d{4}$/;
  const regexHeure = /^([01][0-9]|2[0-3]):[0-5][0-9]$/;

  if (date && !regexDate.test(date)) return "❌ Format de date invalide. Utilise : **10/06/2026**";
  if (heure && !regexHeure.test(heure)) return "❌ Format d'heure invalide. Utilise : **21:00**";

  return null;
}

function extractMentionIds(input) {
  if (!input) return [];
  return [...input.matchAll(/<@!?(\d+)>/g)].map(match => match[1]);
}

function getAllTournamentUsers(tournoi) {
  return [...new Set([
    tournoi.captain,
    ...(tournoi.players || []),
    ...(tournoi.substitutes || [])
  ].filter(Boolean))];
}

function getStatusForUser(tournoi, userId) {
  if (tournoi.captain === userId) return "Capitaine";
  if ((tournoi.substitutes || []).includes(userId)) return "Remplaçant";
  return "Joueur";
}

function getCheckinIcon(tournoi, userId) {
  const status = tournoi.checkins?.[userId];

  if (status === "available") return "✅";
  if (status === "unavailable") return "❌";

  return "⏳";
}

function buildCheckinButtons(tournoiId) {
  return new ActionRowBuilder().addComponents(
      new ButtonBuilder()
          .setCustomId(`checkin_available_${tournoiId}`)
          .setLabel("Je suis disponible")
          .setEmoji("✅")
          .setStyle(ButtonStyle.Success),

      new ButtonBuilder()
          .setCustomId(`checkin_unavailable_${tournoiId}`)
          .setLabel("Je ne suis plus disponible")
          .setEmoji("❌")
          .setStyle(ButtonStyle.Danger)
  );
}

function buildProposalButtons(proposalId) {
  return new ActionRowBuilder().addComponents(
      new ButtonBuilder()
          .setCustomId(`proposal_yes_${proposalId}`)
          .setLabel("Oui")
          .setEmoji("✅")
          .setStyle(ButtonStyle.Success),

      new ButtonBuilder()
          .setCustomId(`proposal_no_${proposalId}`)
          .setLabel("Non")
          .setEmoji("❌")
          .setStyle(ButtonStyle.Danger),

      new ButtonBuilder()
          .setCustomId(`proposal_sub_${proposalId}`)
          .setLabel("Peut-être remplaçant")
          .setEmoji("🔁")
          .setStyle(ButtonStyle.Secondary)
  );
}

function hasCaptainRole(interaction) {
  const roleName = process.env.CAPITAINE_ROLE_NAME || "Capitaine";
  return interaction.member.roles.cache.some(role => role.name === roleName);
}

async function checkCommandChannel(interaction) {
  if (!process.env.COMMAND_CHANNEL_ID) return true;

  if (interaction.channelId !== process.env.COMMAND_CHANNEL_ID) {
    await interaction.editReply({
      content: `❌ Cette commande doit être utilisée dans le salon <#${process.env.COMMAND_CHANNEL_ID}>.`
    });
    return false;
  }

  return true;
}

async function sendDm(userId, message, components = []) {
  try {
    const user = await client.users.fetch(userId);

    await user.send({
      content: message,
      components
    });

    console.log(`MP envoyé à ${user.username}`);
    return true;
  } catch {
    console.log(`Impossible d'envoyer un MP à ${userId}`);
    return false;
  }
}

async function sendLog(message) {
  if (!process.env.LOG_CHANNEL_ID) return;

  const channel = await client.channels.fetch(process.env.LOG_CHANNEL_ID).catch(() => null);
  if (!channel) return;

  await channel.send(message).catch(() => null);
}

function buildRosterEmbed(tournois) {
  const activeTournois = tournois
      .filter(t => t.status !== "cancelled")
      .sort((a, b) => a.timestamp - b.timestamp);

  let description =
      `📢 **ANNONCE ROSTER — TOURNOIS NxR**\n\n` +
      `Les rosters pour les tournois sont désormais confirmés.\n` +
      `Merci aux joueurs sélectionnés d’être présents et prêts avant le début de la rencontre.\n\n` +
      `✅ = confirmé | ❌ = indisponible | ⏳ = en attente\n\n`;

  if (activeTournois.length === 0) {
    description += `Aucun tournoi prévu pour le moment.\n`;
  }

  for (const tournoi of activeTournois) {
    const jour = formatShortDate(tournoi.timestamp);

    description +=
        `━━━━━━━━━━━━━━━━━━\n\n` +
        `**Tournoi prévu ${jour} à ${tournoi.heure} en ${tournoi.format || "format non précisé"}**\n\n` +
        `🎖️ **Capitaine :**\n` +
        `${getCheckinIcon(tournoi, tournoi.captain)} <@${tournoi.captain}>\n\n` +
        `🎮 **Joueurs titulaires :**\n` +
        `${tournoi.players?.length
            ? tournoi.players.map(id => `${getCheckinIcon(tournoi, id)} <@${id}>`).join("\n")
            : "Aucun"}\n\n` +
        `🙋‍♂️ **Remplaçant(e)s :**\n` +
        `${tournoi.substitutes?.length
            ? tournoi.substitutes.map(id => `${getCheckinIcon(tournoi, id)} <@${id}>`).join("\n")
            : "Aucun"}\n\n` +
        `🆔 ID tournoi : \`${tournoi.id}\`\n\n`;
  }

  description +=
      `━━━━━━━━━━━━━━━━━━\n\n` +
      `En cas d’imprévu, merci de prévenir un capitaine à l’avance.\n` +
      `Bon tournoi à vous ! Peu importe qui l’emportera, l’essentiel est de représenter notre nom. ❤️`;

  return new EmbedBuilder()
      .setColor(0x7b2cff)
      .setDescription(description)
      .setFooter({ text: "Colonel Moutarde • NxR" })
      .setTimestamp();
}

async function updateRosterBoard(tournois) {
  if (!process.env.ROSTER_CHANNEL_ID) return;

  const channel = await client.channels.fetch(process.env.ROSTER_CHANNEL_ID).catch(() => null);
  if (!channel) return;

  const state = loadState();
  const embed = buildRosterEmbed(tournois);

  const content = process.env.FORCES_ELITE_ROLE_ID
      ? `<@&${process.env.FORCES_ELITE_ROLE_ID}>`
      : "";

  if (state.rosterMessageId) {
    const oldMessage = await channel.messages.fetch(state.rosterMessageId).catch(() => null);

    if (oldMessage) {
      await oldMessage.edit({ content, embeds: [embed] }).catch(() => null);
      return;
    }
  }

  const sentMessage = await channel.send({ content, embeds: [embed] }).catch(() => null);

  if (sentMessage) {
    state.rosterMessageId = sentMessage.id;
    saveState(state);
  }
}

function buildProposalEmbed(proposal) {
  const yes = [];
  const no = [];
  const sub = [];
  const pending = [];

  for (const userId of proposal.members || []) {
    const response = proposal.responses?.[userId];

    if (response === "yes") yes.push(`<@${userId}>`);
    else if (response === "no") no.push(`<@${userId}>`);
    else if (response === "sub") sub.push(`<@${userId}>`);
    else pending.push(`<@${userId}>`);
  }

  return new EmbedBuilder()
      .setColor(0x7b2cff)
      .setTitle("📢 Proposition tournoi NxR")
      .setDescription(
          `🎮 **Nouveau tournoi proposé**\n\n` +
          `📅 Date : **${formatDateFR(proposal.timestamp)}**\n` +
          `🎯 Format : **${proposal.format}**\n` +
          `👤 Proposé par : <@${proposal.createdBy}>\n\n` +
          `✅ **Disponibles**\n${yes.length ? yes.join("\n") : "Aucun"}\n\n` +
          `🔁 **Peut-être remplaçants**\n${sub.length ? sub.join("\n") : "Aucun"}\n\n` +
          `❌ **Indisponibles**\n${no.length ? no.join("\n") : "Aucun"}\n\n` +
          `⏳ **En attente**\n${pending.length ? pending.join("\n") : "Aucun"}\n\n` +
          `🆔 ID proposition : \`${proposal.id}\``
      )
      .setFooter({ text: "Colonel Moutarde • Proposition tournoi" })
      .setTimestamp();
}

async function updateProposalEmbed(proposal) {
  if (!process.env.PROPOSITION_CHANNEL_ID) return null;

  const channel = await client.channels.fetch(process.env.PROPOSITION_CHANNEL_ID).catch(() => null);
  if (!channel) return null;

  const embed = buildProposalEmbed(proposal);

  if (proposal.messageId) {
    const oldMessage = await channel.messages.fetch(proposal.messageId).catch(() => null);

    if (oldMessage) {
      await oldMessage.edit({ embeds: [embed] }).catch(() => null);
      return proposal.messageId;
    }
  }

  const sentMessage = await channel.send({ embeds: [embed] }).catch(() => null);
  return sentMessage ? sentMessage.id : null;
}

client.once("clientReady", () => {
  console.log(`Bot NxR connecté : ${client.user.tag}`);
});

client.on("interactionCreate", async interaction => {
  try {
    if (interaction.isButton()) {
      const [prefix, status, targetId] = interaction.customId.split("_");

      if (prefix === "proposal") {
        const proposals = loadPropositions();
        const proposal = proposals.find(p => p.id === targetId);

        if (!proposal) {
          return interaction.reply({
            content: "❌ Proposition introuvable.",
            ephemeral: true
          });
        }

        const userId = interaction.user.id;

        if (!proposal.members.includes(userId)) {
          return interaction.reply({
            content: "❌ Tu n’es pas concerné par cette proposition.",
            ephemeral: true
          });
        }

        proposal.responses = proposal.responses || {};
        proposal.responses[userId] = status;
        proposal.updatedAt = Date.now();

        savePropositions(proposals);
        await updateProposalEmbed(proposal);

        return interaction.reply({
          content:
              status === "yes"
                  ? "✅ Réponse enregistrée : tu es disponible."
                  : status === "no"
                      ? "❌ Réponse enregistrée : tu n’es pas disponible."
                      : "🔁 Réponse enregistrée : tu peux être remplaçant.",
          ephemeral: true
        });
      }

      if (prefix !== "checkin") return;

      const tournois = loadTournois();
      const tournoi = tournois.find(t => t.id === targetId);

      if (!tournoi) {
        return interaction.reply({
          content: "❌ Tournoi introuvable.",
          ephemeral: true
        });
      }

      if (tournoi.status === "cancelled") {
        return interaction.reply({
          content: "❌ Ce tournoi est annulé.",
          ephemeral: true
        });
      }

      const userId = interaction.user.id;
      const allUsers = getAllTournamentUsers(tournoi);

      if (!allUsers.includes(userId)) {
        return interaction.reply({
          content: "❌ Tu n’es pas inscrit à ce tournoi.",
          ephemeral: true
        });
      }

      tournoi.checkins = tournoi.checkins || {};
      tournoi.checkins[userId] = status;

      saveTournois(tournois);
      await updateRosterBoard(tournois);

      if (status === "unavailable") {
        await sendLog(
            `❌ **Joueur indisponible**\n` +
            `🆔 Tournoi : \`${tournoi.id}\`\n` +
            `👤 Joueur : <@${userId}>\n` +
            `📌 Statut roster : **${getStatusForUser(tournoi, userId)}**\n` +
            `📅 Date : **${formatDateFR(tournoi.timestamp)}**\n` +
            `🧢 Capitaine : <@${tournoi.captain}>`
        );
      }

      return interaction.reply({
        content: status === "available"
            ? "✅ Présence confirmée."
            : "❌ Indisponibilité enregistrée. Un capitaine sera prévenu.",
        ephemeral: true
      });
    }

    if (!interaction.isChatInputCommand()) return;
    if (interaction.commandName !== "tournoi") return;

    await interaction.deferReply();

    if (!(await checkCommandChannel(interaction))) return;

    if (!hasCaptainRole(interaction)) {
      return interaction.editReply({
        content: "❌ Tu dois avoir le rôle **Capitaine** pour utiliser cette commande."
      });
    }

    const subcommand = interaction.options.getSubcommand();
    const tournois = loadTournois();

    if (subcommand === "proposition") {
      const date = interaction.options.getString("date");
      const heure = interaction.options.getString("heure");
      const format = interaction.options.getString("format");

      const validationError = validateDateHeure(date, heure);
      if (validationError) return interaction.editReply({ content: validationError });

      const tournamentDate = parseDateTime(date, heure);

      if (isNaN(tournamentDate.getTime())) {
        return interaction.editReply({
          content: "❌ Date invalide. Exemple valide : **10/06/2026** à **21:00**"
        });
      }

      const role = interaction.guild.roles.cache.get(process.env.FORCES_ELITE_ROLE_ID);

      if (!role) {
        return interaction.editReply({
          content: "❌ Rôle Forces d'élite introuvable. Vérifie FORCES_ELITE_ROLE_ID dans le .env."
        });
      }

      await interaction.guild.members.fetch();

      const members = role.members
          .filter(member => !member.user.bot)
          .map(member => member.user.id);

      if (members.length === 0) {
        return interaction.editReply({
          content: "❌ Aucun membre trouvé avec le rôle Forces d'élite."
        });
      }

      const proposals = loadPropositions();

      const proposal = {
        id: Date.now().toString(),
        createdBy: interaction.user.id,
        date,
        heure,
        format,
        timestamp: tournamentDate.getTime(),
        members,
        responses: {},
        messageId: null,
        createdAt: Date.now()
      };

      proposal.messageId = await updateProposalEmbed(proposal);

      proposals.push(proposal);
      savePropositions(proposals);

      const dmResults = [];

      for (const userId of members) {
        const success = await sendDm(
            userId,
            `🎮 **Proposition tournoi NxR**\n\n` +
            `Un nouveau tournoi est prévu le **${formatDateFR(proposal.timestamp)}** en **${proposal.format}**.\n\n` +
            `Es-tu disponible ? Tu veux jouer ?\n\n` +
            `Réponds avec les boutons ci-dessous.\n\n` +
            `Ceci est un message automatique du Colonel Moutarde.`,
            [buildProposalButtons(proposal.id)]
        );

        dmResults.push(`${success ? "✅" : "❌"} <@${userId}>`);
      }

      await sendLog(
          `📢 **Proposition tournoi envoyée**\n` +
          `🆔 ID : \`${proposal.id}\`\n` +
          `👤 Créée par : <@${interaction.user.id}>\n` +
          `📅 Date : **${formatDateFR(proposal.timestamp)}**\n` +
          `🎯 Format : **${proposal.format}**\n` +
          `👥 Membres contactés : **${members.length}**`
      );

      return interaction.editReply({
        content:
            `✅ **Proposition tournoi envoyée aux Forces d'élite**\n\n` +
            `🆔 ID : \`${proposal.id}\`\n` +
            `📅 Date : **${formatDateFR(proposal.timestamp)}**\n` +
            `🎯 Format : **${proposal.format}**\n` +
            `👥 Membres contactés : **${members.length}**\n\n` +
            `📩 MP : ${dmResults.join(", ")}`
      });
    }

    if (subcommand === "ajouter") {
      const date = interaction.options.getString("date");
      const heure = interaction.options.getString("heure");
      const format = interaction.options.getString("format");
      const capitaine = interaction.options.getUser("capitaine");
      const joueursInput = interaction.options.getString("joueurs");
      const remplacantsInput = interaction.options.getString("remplacants") || "";

      const validationError = validateDateHeure(date, heure);
      if (validationError) return interaction.editReply({ content: validationError });

      const tournamentDate = parseDateTime(date, heure);

      if (isNaN(tournamentDate.getTime())) {
        return interaction.editReply({
          content: "❌ Date invalide. Exemple valide : **10/06/2026** à **21:00**"
        });
      }

      const playerIds = extractMentionIds(joueursInput);
      const substituteIds = extractMentionIds(remplacantsInput);

      if (playerIds.length === 0) {
        return interaction.editReply({
          content: "❌ Aucun joueur détecté. Mentionne les joueurs avec @."
        });
      }

      const tournoi = {
        id: Date.now().toString(),
        status: "active",
        createdBy: interaction.user.id,
        captain: capitaine.id,
        date,
        heure,
        format,
        timestamp: tournamentDate.getTime(),
        players: playerIds,
        substitutes: substituteIds,
        checkins: {},
        reminder24hSent: false,
        reminder2hSent: false
      };

      tournois.push(tournoi);
      saveTournois(tournois);
      await updateRosterBoard(tournois);

      const dmResults = [];

      for (const userId of getAllTournamentUsers(tournoi)) {
        const rosterStatus = getStatusForUser(tournoi, userId);

        const success = await sendDm(
            userId,
            `🎮 **Tournoi NxR**\n\n` +
            `Tu as été inscrit au tournoi du **${formatDateFR(tournoi.timestamp)}**.\n\n` +
            `🎯 Format : **${tournoi.format}**\n` +
            `🧢 Capitaine : <@${tournoi.captain}>\n` +
            `📌 Statut : **${rosterStatus}**\n\n` +
            `Merci de confirmer ta présence avec les boutons ci-dessous.\n\n` +
            `Ceci est un message automatique du Colonel Moutarde.`,
            [buildCheckinButtons(tournoi.id)]
        );

        dmResults.push(`${success ? "✅" : "❌"} <@${userId}>`);
      }

      await sendLog(
          `📋 **Nouveau tournoi créé**\n` +
          `🆔 ID : \`${tournoi.id}\`\n` +
          `👤 Créé par : <@${interaction.user.id}>\n` +
          `📅 Date : **${formatDateFR(tournoi.timestamp)}**\n` +
          `🎯 Format : **${tournoi.format}**\n` +
          `🧢 Capitaine : <@${tournoi.captain}>`
      );

      return interaction.editReply({
        content:
            `✅ **Tournoi NxR enregistré**\n\n` +
            `🆔 ID : \`${tournoi.id}\`\n` +
            `📅 Date : **${formatDateFR(tournoi.timestamp)}**\n` +
            `🎯 Format : **${tournoi.format}**\n` +
            `🧢 Capitaine : <@${tournoi.captain}>\n` +
            `👥 Joueurs : ${playerIds.map(id => `<@${id}>`).join(", ")}\n` +
            `🔁 Remplaçants : ${substituteIds.length ? substituteIds.map(id => `<@${id}>`).join(", ") : "Aucun"}\n\n` +
            `📩 MP envoyés : ${dmResults.join(", ")}`
      });
    }

    if (subcommand === "liste") {
      const activeTournois = tournois
          .filter(t => t.status !== "cancelled")
          .sort((a, b) => a.timestamp - b.timestamp);

      if (activeTournois.length === 0) {
        return interaction.editReply({ content: "Aucun tournoi prévu." });
      }

      return interaction.editReply({
        content:
            `📋 **Tournois NxR prévus**\n\n` +
            activeTournois.map(t =>
                `🆔 \`${t.id}\` — **${formatDateFR(t.timestamp)}** — **${t.format || "?"}** — Capitaine : <@${t.captain}>`
            ).join("\n")
      });
    }

    if (subcommand === "annuler") {
      const id = interaction.options.getString("id");
      const raison = interaction.options.getString("raison") || "Aucune raison précisée";
      const tournoi = tournois.find(t => t.id === id);

      if (!tournoi) return interaction.editReply({ content: "❌ Tournoi introuvable." });
      if (tournoi.status === "cancelled") return interaction.editReply({ content: "❌ Ce tournoi est déjà annulé." });

      tournoi.status = "cancelled";
      tournoi.cancelledBy = interaction.user.id;
      tournoi.cancelReason = raison;
      tournoi.cancelledAt = Date.now();

      saveTournois(tournois);
      await updateRosterBoard(tournois);

      const dmResults = [];

      for (const userId of getAllTournamentUsers(tournoi)) {
        const rosterStatus = getStatusForUser(tournoi, userId);

        const success = await sendDm(
            userId,
            `🎮 **Tournoi NxR**\n\n` +
            `❌ Le tournoi du **${formatDateFR(tournoi.timestamp)}** a été annulé.\n\n` +
            `🧢 Capitaine : <@${tournoi.captain}>\n` +
            `📌 Statut : **${rosterStatus}**\n\n` +
            `Nous nous excusons pour la gêne occasionnée. Restez attentifs aux prochaines annonces pour les futurs tournois.\n\n` +
            `Ceci est un message automatique du Colonel Moutarde.`
        );

        dmResults.push(`${success ? "✅" : "❌"} <@${userId}>`);
      }

      await sendLog(
          `❌ **Tournoi annulé**\n` +
          `🆔 ID : \`${tournoi.id}\`\n` +
          `👤 Annulé par : <@${interaction.user.id}>\n` +
          `📅 Date : **${formatDateFR(tournoi.timestamp)}**\n` +
          `📝 Raison : ${raison}`
      );

      return interaction.editReply({
        content:
            `❌ **Tournoi annulé**\n\n` +
            `🆔 ID : \`${tournoi.id}\`\n` +
            `📅 Date : **${formatDateFR(tournoi.timestamp)}**\n` +
            `📩 MP envoyés : ${dmResults.join(", ")}`
      });
    }

    if (subcommand === "modifier") {
      const id = interaction.options.getString("id");
      const tournoi = tournois.find(t => t.id === id);

      if (!tournoi) return interaction.editReply({ content: "❌ Tournoi introuvable." });
      if (tournoi.status === "cancelled") return interaction.editReply({ content: "❌ Impossible de modifier un tournoi annulé." });

      const newDate = interaction.options.getString("date");
      const newHeure = interaction.options.getString("heure");
      const newFormat = interaction.options.getString("format");
      const newCapitaine = interaction.options.getUser("capitaine");
      const newJoueursInput = interaction.options.getString("joueurs");
      const newRemplacantsInput = interaction.options.getString("remplacants");

      const finalDate = newDate || tournoi.date;
      const finalHeure = newHeure || tournoi.heure;

      const validationError = validateDateHeure(finalDate, finalHeure);
      if (validationError) return interaction.editReply({ content: validationError });

      const newTimestamp = parseDateTime(finalDate, finalHeure).getTime();
      if (isNaN(newTimestamp)) return interaction.editReply({ content: "❌ Date invalide." });

      tournoi.date = finalDate;
      tournoi.heure = finalHeure;
      tournoi.timestamp = newTimestamp;

      if (newFormat) tournoi.format = newFormat;
      if (newCapitaine) tournoi.captain = newCapitaine.id;

      if (newJoueursInput) {
        const newPlayers = extractMentionIds(newJoueursInput);
        if (newPlayers.length === 0) {
          return interaction.editReply({ content: "❌ Aucun joueur détecté dans la modification." });
        }
        tournoi.players = newPlayers;
      }

      if (newRemplacantsInput !== null) {
        tournoi.substitutes = extractMentionIds(newRemplacantsInput);
      }

      tournoi.checkins = tournoi.checkins || {};
      tournoi.reminder24hSent = false;
      tournoi.reminder2hSent = false;
      tournoi.updatedBy = interaction.user.id;
      tournoi.updatedAt = Date.now();

      saveTournois(tournois);
      await updateRosterBoard(tournois);

      const dmResults = [];

      for (const userId of getAllTournamentUsers(tournoi)) {
        const rosterStatus = getStatusForUser(tournoi, userId);

        const success = await sendDm(
            userId,
            `🎮 **Tournoi NxR**\n\n` +
            `✏️ Le tournoi a été modifié.\n\n` +
            `📅 Nouvelle date : **${formatDateFR(tournoi.timestamp)}**\n` +
            `🎯 Format : **${tournoi.format || "Non précisé"}**\n` +
            `🧢 Capitaine : <@${tournoi.captain}>\n` +
            `📌 Statut : **${rosterStatus}**\n\n` +
            `Merci de confirmer ta présence avec les boutons ci-dessous.\n\n` +
            `Ceci est un message automatique du Colonel Moutarde.`,
            [buildCheckinButtons(tournoi.id)]
        );

        dmResults.push(`${success ? "✅" : "❌"} <@${userId}>`);
      }

      await sendLog(
          `✏️ **Tournoi modifié**\n` +
          `🆔 ID : \`${tournoi.id}\`\n` +
          `👤 Modifié par : <@${interaction.user.id}>\n` +
          `📅 Date : **${formatDateFR(tournoi.timestamp)}**\n` +
          `🎯 Format : **${tournoi.format || "Non précisé"}**\n` +
          `🧢 Capitaine : <@${tournoi.captain}>`
      );

      return interaction.editReply({
        content:
            `✏️ **Tournoi modifié**\n\n` +
            `🆔 ID : \`${tournoi.id}\`\n` +
            `📅 Date : **${formatDateFR(tournoi.timestamp)}**\n` +
            `🎯 Format : **${tournoi.format || "Non précisé"}**\n` +
            `🧢 Capitaine : <@${tournoi.captain}>\n` +
            `👥 Joueurs : ${tournoi.players.map(id => `<@${id}>`).join(", ")}\n` +
            `🔁 Remplaçants : ${tournoi.substitutes.length ? tournoi.substitutes.map(id => `<@${id}>`).join(", ") : "Aucun"}\n\n` +
            `📩 MP envoyés : ${dmResults.join(", ")}`
      });
    }

  } catch (error) {
    console.error("Erreur interactionCreate :", error);

    if (interaction.deferred || interaction.replied) {
      return interaction.editReply({ content: "❌ Une erreur est survenue." });
    }

    return interaction.reply({
      content: "❌ Une erreur est survenue.",
      ephemeral: true
    });
  }
});

cron.schedule("* * * * *", async () => {
  const now = Date.now();
  const tournois = loadTournois();
  let updated = false;

  for (const tournoi of tournois) {
    if (tournoi.status === "cancelled") continue;

    const timeLeft = tournoi.timestamp - now;

    const shouldSend24h =
        timeLeft <= 24 * 60 * 60 * 1000 &&
        timeLeft > 23 * 60 * 60 * 1000 &&
        !tournoi.reminder24hSent;

    const shouldSend2h =
        timeLeft <= 2 * 60 * 60 * 1000 &&
        timeLeft > 1 * 60 * 60 * 1000 &&
        !tournoi.reminder2hSent;

    if (!shouldSend24h && !shouldSend2h) continue;

    const type = shouldSend24h ? "24h" : "2h";

    for (const userId of getAllTournamentUsers(tournoi)) {
      const rosterStatus = getStatusForUser(tournoi, userId);

      await sendDm(
          userId,
          `⚔️ **Rappel tournoi NxR**\n\n` +
          `Tu es inscrit au tournoi prévu le **${formatDateFR(tournoi.timestamp)}**.\n\n` +
          `🎯 Format : **${tournoi.format || "Non précisé"}**\n` +
          `🧢 Capitaine : <@${tournoi.captain}>\n` +
          `📌 Statut : **${rosterStatus}**\n` +
          `⏰ Rappel : **${type} avant le tournoi**.\n\n` +
          `Merci de confirmer que tu es toujours disponible avec les boutons ci-dessous.\n\n` +
          `Ceci est un message automatique du Colonel Moutarde.`,
          [buildCheckinButtons(tournoi.id)]
      );
    }

    if (shouldSend24h) tournoi.reminder24hSent = true;
    if (shouldSend2h) tournoi.reminder2hSent = true;

    await sendLog(
        `⏰ **Rappel ${type} envoyé**\n` +
        `🆔 ID : \`${tournoi.id}\`\n` +
        `📅 Date : **${formatDateFR(tournoi.timestamp)}**`
    );

    updated = true;
  }

  if (updated) {
    saveTournois(tournois);
  }
});

client.login(process.env.TOKEN);