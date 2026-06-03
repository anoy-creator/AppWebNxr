import cron from 'node-cron';
import axios from 'axios';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import {
    ActionRowBuilder,
    ButtonBuilder,
    ButtonStyle,
    EmbedBuilder,
    SlashCommandBuilder,
} from 'discord.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const botRoot = path.resolve(__dirname, '..');

const DB_FILE = path.join(botRoot, 'tournois.json');
const STATE_FILE = path.join(botRoot, 'roster-state.json');
const ALLOWED_FORMATS = ['1v1', '2v2', '3v3', '4v4', '5v5', '6v6'];

function formatDateFR(timestamp) {
    return new Date(timestamp).toLocaleString('fr-FR', {
        timeZone: 'Europe/Paris',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatShortDate(timestamp) {
    return new Date(timestamp).toLocaleDateString('fr-FR', {
        timeZone: 'Europe/Paris',
        weekday: 'long',
    });
}

function loadJson(file, fallback) {
    try {
        if (!fs.existsSync(file)) {
            saveJson(file, fallback);
            return fallback;
        }

        const content = fs.readFileSync(file, 'utf8')
            .replace(/^\uFEFF/, '')
            .replace(/\0/g, '')
            .trim();

        if (!content) {
            saveJson(file, fallback);
            return fallback;
        }

        return JSON.parse(content);
    } catch {
        saveJson(file, fallback);
        return fallback;
    }
}

function saveJson(file, data) {
    fs.writeFileSync(file, JSON.stringify(data, null, 2), 'utf8');
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

function parseDateTime(date, heure) {
    const [day, month, year] = date.split('/').map(Number);
    const [hours, minutes] = heure.split(':').map(Number);

    if (!day || !month || !year || hours === undefined || minutes === undefined) {
        return new Date('invalid');
    }

    return new Date(year, month - 1, day, hours, minutes, 0);
}

function validateDateHeure(date, heure) {
    const regexDate = /^([0-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/\d{4}$/;
    const regexHeure = /^([01][0-9]|2[0-3]):[0-5][0-9]$/;

    if (date && !regexDate.test(date)) return 'Format de date invalide. Utilise : 10/06/2026';
    if (heure && !regexHeure.test(heure)) return "Format d'heure invalide. Utilise : 21:00";

    return null;
}

function validateTournamentFormat(format) {
    if (!ALLOWED_FORMATS.includes(format)) {
        return `Format de tournoi invalide. Utilise : ${ALLOWED_FORMATS.join(', ')}`;
    }

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
        ...(tournoi.substitutes || []),
    ].filter(Boolean))];
}

function getStatusForUser(tournoi, userId) {
    if (tournoi.captain === userId) return 'Capitaine';
    if ((tournoi.substitutes || []).includes(userId)) return 'Remplacant';
    return 'Joueur';
}

function getCheckinIcon(tournoi, userId) {
    const status = tournoi.checkins?.[userId];

    if (status === 'available') return 'OK';
    if (status === 'unavailable') return 'NON';

    return '...';
}

function buildCheckinButtons(tournoiId) {
    return new ActionRowBuilder().addComponents(
        new ButtonBuilder()
            .setCustomId(`checkin_available_${tournoiId}`)
            .setLabel('Je suis disponible')
            .setStyle(ButtonStyle.Success),
        new ButtonBuilder()
            .setCustomId(`checkin_unavailable_${tournoiId}`)
            .setLabel('Je ne suis plus disponible')
            .setStyle(ButtonStyle.Danger),
    );
}

function hasCaptainRole(interaction) {
    const roleName = process.env.CAPITAINE_ROLE_NAME || 'Capitaine';
    return interaction.member.roles.cache.some(role => role.name === roleName);
}

async function checkCommandChannel(interaction) {
    if (!process.env.COMMAND_CHANNEL_ID) return true;

    if (interaction.channelId !== process.env.COMMAND_CHANNEL_ID) {
        await interaction.editReply({
            content: `Cette commande doit etre utilisee dans le salon <#${process.env.COMMAND_CHANNEL_ID}>.`,
        });
        return false;
    }

    return true;
}

async function sendDm(client, userId, message, components = []) {
    try {
        const user = await client.users.fetch(userId);

        await user.send({
            content: message,
            components,
        });

        console.log(`MP envoye a ${user.username}`);
        return true;
    } catch {
        console.log(`Impossible d'envoyer un MP a ${userId}`);
        return false;
    }
}

async function sendLog(client, message) {
    if (!process.env.LOG_CHANNEL_ID) return;

    const channel = await client.channels.fetch(process.env.LOG_CHANNEL_ID).catch(() => null);
    if (!channel) return;

    await channel.send(message).catch(() => null);
}

async function syncTournamentWithSite(tournoi) {
    if (!process.env.ROUTE_API || process.env.ROUTE_API === '#' || !process.env.API_KEY || process.env.API_KEY === '#') {
        return {
            success: false,
            message: 'API du site non configuree',
        };
    }

    try {
        const response = await axios.post(
            `${process.env.ROUTE_API}/discord/add-tournois`,
            tournoi,
            {
                headers: {
                    'x-api-key': process.env.API_KEY,
                },
            },
        );

        return {
            success: true,
            message: response.data?.message || 'Event cree sur le site',
            event: response.data?.created?.[0] || null,
        };
    } catch (error) {
        console.error('Erreur synchronisation site :', error.response?.data || error.message);

        return {
            success: false,
            message: error.response?.data?.message || error.message,
        };
    }
}

function buildRosterEmbed(tournois) {
    const activeTournois = tournois
        .filter(t => t.status !== 'cancelled')
        .sort((a, b) => a.timestamp - b.timestamp);

    let description =
        '**ANNONCE ROSTER - TOURNOIS NxR**\n\n' +
        'Les rosters pour les tournois sont desormais confirmes.\n' +
        'Merci aux joueurs selectionnes d etre presents et prets avant le debut de la rencontre.\n\n' +
        'OK = confirme | NON = indisponible | ... = en attente\n\n';

    if (activeTournois.length === 0) {
        description += 'Aucun tournoi prevu pour le moment.\n';
    }

    for (const tournoi of activeTournois) {
        const jour = formatShortDate(tournoi.timestamp);
        const heure = tournoi.heure;

        description +=
            '------------------------------\n\n' +
            `**Tournoi prevu ${jour} a ${heure} en ${tournoi.format || 'format non precise'}**\n\n` +
            '**Capitaine :**\n' +
            `${getCheckinIcon(tournoi, tournoi.captain)} <@${tournoi.captain}>\n\n` +
            '**Joueurs titulaires :**\n' +
            `${tournoi.players?.length
                ? tournoi.players.map(id => `${getCheckinIcon(tournoi, id)} <@${id}>`).join('\n')
                : 'Aucun'}\n\n` +
            '**Remplacant(e)s :**\n' +
            `${tournoi.substitutes?.length
                ? tournoi.substitutes.map(id => `${getCheckinIcon(tournoi, id)} <@${id}>`).join('\n')
                : 'Aucun'}\n\n` +
            `ID tournoi : \`${tournoi.id}\`\n\n`;
    }

    description +=
        '------------------------------\n\n' +
        'En cas d imprevu, merci de prevenir un capitaine a l avance.\n' +
        'Bon tournoi a vous ! Peu importe qui l emportera, l essentiel est de representer notre nom.';

    return new EmbedBuilder()
        .setColor(0x7b2cff)
        .setDescription(description)
        .setFooter({ text: 'Colonel Moutarde - NxR' })
        .setTimestamp();
}

async function updateRosterBoard(client, tournois) {
    if (!process.env.ROSTER_CHANNEL_ID) return;

    const channel = await client.channels.fetch(process.env.ROSTER_CHANNEL_ID).catch(() => null);
    if (!channel) return;

    const state = loadState();
    const embed = buildRosterEmbed(tournois);

    const content = process.env.FORCES_ELITE_ROLE_ID
        ? `<@&${process.env.FORCES_ELITE_ROLE_ID}>`
        : '';

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

async function handleCheckin(interaction, client) {
    const [prefix, status, tournoiId] = interaction.customId.split('_');

    if (prefix !== 'checkin') return false;

    const tournois = loadTournois();
    const tournoi = tournois.find(t => t.id === tournoiId);

    if (!tournoi) {
        await interaction.reply({ content: 'Tournoi introuvable.', ephemeral: true });
        return true;
    }

    if (tournoi.status === 'cancelled') {
        await interaction.reply({ content: 'Ce tournoi est annule.', ephemeral: true });
        return true;
    }

    const userId = interaction.user.id;
    const allUsers = getAllTournamentUsers(tournoi);

    if (!allUsers.includes(userId)) {
        await interaction.reply({ content: 'Tu n es pas inscrit a ce tournoi.', ephemeral: true });
        return true;
    }

    tournoi.checkins = tournoi.checkins || {};
    tournoi.checkins[userId] = status;

    saveTournois(tournois);
    await updateRosterBoard(client, tournois);

    if (status === 'unavailable') {
        await sendLog(
            client,
            '**Joueur indisponible**\n' +
            `Tournoi : \`${tournoi.id}\`\n` +
            `Joueur : <@${userId}>\n` +
            `Statut roster : **${getStatusForUser(tournoi, userId)}**\n` +
            `Date : **${formatDateFR(tournoi.timestamp)}**\n` +
            `Capitaine : <@${tournoi.captain}>`,
        );
    }

    await interaction.reply({
        content: status === 'available'
            ? 'Presence confirmee.'
            : 'Indisponibilite enregistree. Un capitaine sera prevenu.',
        ephemeral: true,
    });

    return true;
}

async function executeInteraction(interaction, client) {
    await interaction.deferReply();

    if (!(await checkCommandChannel(interaction))) return;

    if (!hasCaptainRole(interaction)) {
        return interaction.editReply({
            content: 'Tu dois avoir le role Capitaine pour utiliser cette commande.',
        });
    }

    const subcommand = interaction.options.getSubcommand();
    const tournois = loadTournois();

    if (subcommand === 'ajouter') {
        const date = interaction.options.getString('date');
        const heure = interaction.options.getString('heure');
        const format = interaction.options.getString('format');
        const capitaine = interaction.options.getUser('capitaine');
        const joueursInput = interaction.options.getString('joueurs');
        const remplacantsInput = interaction.options.getString('remplacants') || '';

        const validationError = validateDateHeure(date, heure);
        if (validationError) return interaction.editReply({ content: validationError });

        const formatError = validateTournamentFormat(format);
        if (formatError) return interaction.editReply({ content: formatError });

        const tournamentDate = parseDateTime(date, heure);

        if (isNaN(tournamentDate.getTime())) {
            return interaction.editReply({
                content: 'Date invalide. Exemple valide : 10/06/2026 a 21:00',
            });
        }

        const playerIds = extractMentionIds(joueursInput);
        const substituteIds = extractMentionIds(remplacantsInput);

        if (playerIds.length === 0) {
            return interaction.editReply({
                content: 'Aucun joueur detecte. Mentionne les joueurs avec @.',
            });
        }

        const tournoi = {
            id: Date.now().toString(),
            status: 'active',
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
            reminder2hSent: false,
        };

        tournois.push(tournoi);
        saveTournois(tournois);
        await updateRosterBoard(client, tournois);
        const siteSync = await syncTournamentWithSite(tournoi);

        const dmResults = [];

        for (const userId of getAllTournamentUsers(tournoi)) {
            const rosterStatus = getStatusForUser(tournoi, userId);
            const success = await sendDm(
                client,
                userId,
                '**Tournoi NxR**\n\n' +
                `Tu as ete inscrit au tournoi du **${formatDateFR(tournoi.timestamp)}**.\n\n` +
                `Format : **${tournoi.format}**\n` +
                `Capitaine : <@${tournoi.captain}>\n` +
                `Statut : **${rosterStatus}**\n\n` +
                'Merci de confirmer ta presence avec les boutons ci-dessous.\n\n' +
                'Ceci est un message automatique du Colonel Moutarde.',
                [buildCheckinButtons(tournoi.id)],
            );

            dmResults.push(`${success ? 'OK' : 'NON'} <@${userId}>`);
        }

        await sendLog(
            client,
            '**Nouveau tournoi cree**\n' +
            `ID : \`${tournoi.id}\`\n` +
            `Cree par : <@${interaction.user.id}>\n` +
            `Date : **${formatDateFR(tournoi.timestamp)}**\n` +
            `Format : **${tournoi.format}**\n` +
            `Capitaine : <@${tournoi.captain}>`,
        );

        return interaction.editReply({
            content:
                '**Tournoi NxR enregistre**\n\n' +
                `ID : \`${tournoi.id}\`\n` +
                `Date : **${formatDateFR(tournoi.timestamp)}**\n` +
                `Format : **${tournoi.format}**\n` +
                `Capitaine : <@${tournoi.captain}>\n` +
                `Joueurs : ${playerIds.map(id => `<@${id}>`).join(', ')}\n` +
                `Remplacants : ${substituteIds.length ? substituteIds.map(id => `<@${id}>`).join(', ') : 'Aucun'}\n\n` +
                `Site : ${siteSync.success ? `event cree (${siteSync.event?.title || 'Tournoi'})` : `non synchronise - ${siteSync.message}`}\n` +
                `MP envoyes : ${dmResults.join(', ')}`,
        });
    }

    if (subcommand === 'liste') {
        const activeTournois = tournois
            .filter(t => t.status !== 'cancelled')
            .sort((a, b) => a.timestamp - b.timestamp);

        if (activeTournois.length === 0) {
            return interaction.editReply({ content: 'Aucun tournoi prevu.' });
        }

        return interaction.editReply({
            content:
                '**Tournois NxR prevus**\n\n' +
                activeTournois.map(t =>
                    `\`${t.id}\` - **${formatDateFR(t.timestamp)}** - **${t.format || '?'}** - Capitaine : <@${t.captain}>`,
                ).join('\n'),
        });
    }

    if (subcommand === 'annuler') {
        const id = interaction.options.getString('id');
        const raison = interaction.options.getString('raison') || 'Aucune raison precisee';
        const tournoi = tournois.find(t => t.id === id);

        if (!tournoi) return interaction.editReply({ content: 'Tournoi introuvable.' });
        if (tournoi.status === 'cancelled') return interaction.editReply({ content: 'Ce tournoi est deja annule.' });

        tournoi.status = 'cancelled';
        tournoi.cancelledBy = interaction.user.id;
        tournoi.cancelReason = raison;
        tournoi.cancelledAt = Date.now();

        saveTournois(tournois);
        await updateRosterBoard(client, tournois);

        const dmResults = [];

        for (const userId of getAllTournamentUsers(tournoi)) {
            const rosterStatus = getStatusForUser(tournoi, userId);
            const success = await sendDm(
                client,
                userId,
                '**Tournoi NxR**\n\n' +
                `Le tournoi du **${formatDateFR(tournoi.timestamp)}** a ete annule.\n\n` +
                `Capitaine : <@${tournoi.captain}>\n` +
                `Statut : **${rosterStatus}**\n\n` +
                'Nous nous excusons pour la gene occasionnee.\n\n' +
                'Ceci est un message automatique du Colonel Moutarde.',
            );

            dmResults.push(`${success ? 'OK' : 'NON'} <@${userId}>`);
        }

        await sendLog(
            client,
            '**Tournoi annule**\n' +
            `ID : \`${tournoi.id}\`\n` +
            `Annule par : <@${interaction.user.id}>\n` +
            `Date : **${formatDateFR(tournoi.timestamp)}**\n` +
            `Raison : ${raison}`,
        );

        return interaction.editReply({
            content:
                '**Tournoi annule**\n\n' +
                `ID : \`${tournoi.id}\`\n` +
                `Date : **${formatDateFR(tournoi.timestamp)}**\n` +
                `MP envoyes : ${dmResults.join(', ')}`,
        });
    }

    if (subcommand === 'modifier') {
        const id = interaction.options.getString('id');
        const tournoi = tournois.find(t => t.id === id);

        if (!tournoi) return interaction.editReply({ content: 'Tournoi introuvable.' });
        if (tournoi.status === 'cancelled') return interaction.editReply({ content: 'Impossible de modifier un tournoi annule.' });

        const newDate = interaction.options.getString('date');
        const newHeure = interaction.options.getString('heure');
        const newFormat = interaction.options.getString('format');
        const newCapitaine = interaction.options.getUser('capitaine');
        const newJoueursInput = interaction.options.getString('joueurs');
        const newRemplacantsInput = interaction.options.getString('remplacants');

        const finalDate = newDate || tournoi.date;
        const finalHeure = newHeure || tournoi.heure;

        const validationError = validateDateHeure(finalDate, finalHeure);
        if (validationError) return interaction.editReply({ content: validationError });

        if (newFormat) {
            const formatError = validateTournamentFormat(newFormat);
            if (formatError) return interaction.editReply({ content: formatError });
        }

        const newTimestamp = parseDateTime(finalDate, finalHeure).getTime();
        if (isNaN(newTimestamp)) return interaction.editReply({ content: 'Date invalide.' });

        tournoi.date = finalDate;
        tournoi.heure = finalHeure;
        tournoi.timestamp = newTimestamp;

        if (newFormat) tournoi.format = newFormat;
        if (newCapitaine) tournoi.captain = newCapitaine.id;

        if (newJoueursInput) {
            const newPlayers = extractMentionIds(newJoueursInput);
            if (newPlayers.length === 0) {
                return interaction.editReply({ content: 'Aucun joueur detecte dans la modification.' });
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
        await updateRosterBoard(client, tournois);

        const dmResults = [];

        for (const userId of getAllTournamentUsers(tournoi)) {
            const rosterStatus = getStatusForUser(tournoi, userId);
            const success = await sendDm(
                client,
                userId,
                '**Tournoi NxR**\n\n' +
                'Le tournoi a ete modifie.\n\n' +
                `Nouvelle date : **${formatDateFR(tournoi.timestamp)}**\n` +
                `Format : **${tournoi.format || 'Non precise'}**\n` +
                `Capitaine : <@${tournoi.captain}>\n` +
                `Statut : **${rosterStatus}**\n\n` +
                'Merci de confirmer ta presence avec les boutons ci-dessous.\n\n' +
                'Ceci est un message automatique du Colonel Moutarde.',
                [buildCheckinButtons(tournoi.id)],
            );

            dmResults.push(`${success ? 'OK' : 'NON'} <@${userId}>`);
        }

        await sendLog(
            client,
            '**Tournoi modifie**\n' +
            `ID : \`${tournoi.id}\`\n` +
            `Modifie par : <@${interaction.user.id}>\n` +
            `Date : **${formatDateFR(tournoi.timestamp)}**\n` +
            `Format : **${tournoi.format || 'Non precise'}**\n` +
            `Capitaine : <@${tournoi.captain}>`,
        );

        return interaction.editReply({
            content:
                '**Tournoi modifie**\n\n' +
                `ID : \`${tournoi.id}\`\n` +
                `Date : **${formatDateFR(tournoi.timestamp)}**\n` +
                `Format : **${tournoi.format || 'Non precise'}**\n` +
                `Capitaine : <@${tournoi.captain}>\n` +
                `Joueurs : ${(tournoi.players || []).map(id => `<@${id}>`).join(', ')}\n` +
                `Remplacants : ${tournoi.substitutes?.length ? tournoi.substitutes.map(id => `<@${id}>`).join(', ') : 'Aucun'}\n\n` +
                `MP envoyes : ${dmResults.join(', ')}`,
        });
    }
}

function setup(client) {
    cron.schedule('* * * * *', async () => {
        const now = Date.now();
        const tournois = loadTournois();
        let updated = false;

        for (const tournoi of tournois) {
            if (tournoi.status === 'cancelled') continue;

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

            const type = shouldSend24h ? '24h' : '2h';

            for (const userId of getAllTournamentUsers(tournoi)) {
                const rosterStatus = getStatusForUser(tournoi, userId);

                await sendDm(
                    client,
                    userId,
                    '**Rappel tournoi NxR**\n\n' +
                    `Tu es inscrit au tournoi prevu le **${formatDateFR(tournoi.timestamp)}**.\n\n` +
                    `Format : **${tournoi.format || 'Non precise'}**\n` +
                    `Capitaine : <@${tournoi.captain}>\n` +
                    `Statut : **${rosterStatus}**\n` +
                    `Rappel : **${type} avant le tournoi**.\n\n` +
                    'Merci de confirmer que tu es toujours disponible avec les boutons ci-dessous.\n\n' +
                    'Ceci est un message automatique du Colonel Moutarde.',
                    [buildCheckinButtons(tournoi.id)],
                );
            }

            if (shouldSend24h) tournoi.reminder24hSent = true;
            if (shouldSend2h) tournoi.reminder2hSent = true;

            await sendLog(
                client,
                `**Rappel ${type} envoye**\n` +
                `ID : \`${tournoi.id}\`\n` +
                `Date : **${formatDateFR(tournoi.timestamp)}**`,
            );

            updated = true;
        }

        if (updated) {
            saveTournois(tournois);
        }
    });
}

export default {
    name: 'tournoi',
    description: 'Gestion des tournois NxR (/tournoi)',
    slashOnly: true,
    data: new SlashCommandBuilder()
        .setName('tournoi')
        .setDescription('Gestion des tournois NxR')
        .addSubcommand(sub =>
            sub
                .setName('ajouter')
                .setDescription('Ajouter un tournoi')
                .addStringOption(opt =>
                    opt.setName('date')
                        .setDescription('Date : 10/06/2026')
                        .setRequired(true),
                )
                .addStringOption(opt =>
                    opt.setName('heure')
                        .setDescription('Heure : 21:00')
                        .setRequired(true),
                )
                .addStringOption(opt =>
                    opt.setName('format')
                        .setDescription('Format du tournoi')
                        .setRequired(true)
                        .addChoices(
                            { name: '1v1', value: '1v1' },
                            { name: '2v2', value: '2v2' },
                            { name: '3v3', value: '3v3' },
                            { name: '4v4', value: '4v4' },
                            { name: '5v5', value: '5v5' },
                            { name: '6v6', value: '6v6' },
                        ),
                )
                .addUserOption(opt =>
                    opt.setName('capitaine')
                        .setDescription('Capitaine du tournoi')
                        .setRequired(true),
                )
                .addStringOption(opt =>
                    opt.setName('joueurs')
                        .setDescription('Joueurs titulaires : @joueur1 @joueur2')
                        .setRequired(true),
                )
                .addStringOption(opt =>
                    opt.setName('remplacants')
                        .setDescription('Remplacants : @joueur1 @joueur2')
                        .setRequired(false),
                ),
        )
        .addSubcommand(sub =>
            sub
                .setName('liste')
                .setDescription('Afficher les tournois prevus'),
        )
        .addSubcommand(sub =>
            sub
                .setName('annuler')
                .setDescription('Annuler un tournoi')
                .addStringOption(opt =>
                    opt.setName('id')
                        .setDescription('ID du tournoi')
                        .setRequired(true),
                )
                .addStringOption(opt =>
                    opt.setName('raison')
                        .setDescription('Raison de l annulation')
                        .setRequired(false),
                ),
        )
        .addSubcommand(sub =>
            sub
                .setName('modifier')
                .setDescription('Modifier un tournoi')
                .addStringOption(opt =>
                    opt.setName('id')
                        .setDescription('ID du tournoi')
                        .setRequired(true),
                )
                .addStringOption(opt =>
                    opt.setName('date')
                        .setDescription('Nouvelle date : 10/06/2026')
                        .setRequired(false),
                )
                .addStringOption(opt =>
                    opt.setName('heure')
                        .setDescription('Nouvelle heure : 21:00')
                        .setRequired(false),
                )
                .addStringOption(opt =>
                    opt.setName('format')
                        .setDescription('Nouveau format')
                        .setRequired(false)
                        .addChoices(
                            { name: '1v1', value: '1v1' },
                            { name: '2v2', value: '2v2' },
                            { name: '3v3', value: '3v3' },
                            { name: '4v4', value: '4v4' },
                            { name: '5v5', value: '5v5' },
                            { name: '6v6', value: '6v6' },
                        ),
                )
                .addUserOption(opt =>
                    opt.setName('capitaine')
                        .setDescription('Nouveau capitaine')
                        .setRequired(false),
                )
                .addStringOption(opt =>
                    opt.setName('joueurs')
                        .setDescription('Nouveaux joueurs titulaires : @joueur1 @joueur2')
                        .setRequired(false),
                )
                .addStringOption(opt =>
                    opt.setName('remplacants')
                        .setDescription('Nouveaux remplacants : @joueur1 @joueur2')
                        .setRequired(false),
                ),
        ),
    async execute(message) {
        await message.reply('Cette commande s utilise avec /tournoi.');
    },
    executeInteraction,
    handleButton: handleCheckin,
    setup,
};
