require("dotenv").config();

const { REST, Routes, SlashCommandBuilder } = require("discord.js");

const commands = [
  new SlashCommandBuilder()
    .setName("tournoi")
    .setDescription("Gestion des tournois NxR")

    .addSubcommand(sub =>
      sub
        .setName("ajouter")
        .setDescription("Ajouter un tournoi")
        .addStringOption(opt =>
          opt.setName("date")
            .setDescription("Date : 10/06/2026")
            .setRequired(true)
        )
        .addStringOption(opt =>
          opt.setName("heure")
            .setDescription("Heure : 21:00")
            .setRequired(true)
        )
        .addStringOption(opt =>
          opt.setName("format")
            .setDescription("Format du tournoi : 3v3, 4v4, 5v5...")
            .setRequired(true)
        )
        .addUserOption(opt =>
          opt.setName("capitaine")
            .setDescription("Capitaine du tournoi")
            .setRequired(true)
        )
        .addStringOption(opt =>
          opt.setName("joueurs")
            .setDescription("Joueurs titulaires : @joueur1 @joueur2")
            .setRequired(true)
        )
        .addStringOption(opt =>
          opt.setName("remplacants")
            .setDescription("Remplaçants : @joueur1 @joueur2")
            .setRequired(false)
        )
    )

    .addSubcommand(sub =>
      sub
        .setName("liste")
        .setDescription("Afficher les tournois prévus")
    )

    .addSubcommand(sub =>
      sub
        .setName("annuler")
        .setDescription("Annuler un tournoi")
        .addStringOption(opt =>
          opt.setName("id")
            .setDescription("ID du tournoi")
            .setRequired(true)
        )
        .addStringOption(opt =>
          opt.setName("raison")
            .setDescription("Raison de l’annulation")
            .setRequired(false)
        )
    )

    .addSubcommand(sub =>
      sub
        .setName("modifier")
        .setDescription("Modifier un tournoi")
        .addStringOption(opt =>
          opt.setName("id")
            .setDescription("ID du tournoi")
            .setRequired(true)
        )
        .addStringOption(opt =>
          opt.setName("date")
            .setDescription("Nouvelle date : 10/06/2026")
            .setRequired(false)
        )
        .addStringOption(opt =>
          opt.setName("heure")
            .setDescription("Nouvelle heure : 21:00")
            .setRequired(false)
        )
        .addStringOption(opt =>
          opt.setName("format")
            .setDescription("Nouveau format : 3v3, 4v4, 5v5...")
            .setRequired(false)
        )
        .addUserOption(opt =>
          opt.setName("capitaine")
            .setDescription("Nouveau capitaine")
            .setRequired(false)
        )
        .addStringOption(opt =>
          opt.setName("joueurs")
            .setDescription("Nouveaux joueurs titulaires : @joueur1 @joueur2")
            .setRequired(false)
        )
        .addStringOption(opt =>
          opt.setName("remplacants")
            .setDescription("Nouveaux remplaçants : @joueur1 @joueur2")
            .setRequired(false)
        )
    )
].map(cmd => cmd.toJSON());

const rest = new REST({ version: "10" }).setToken(process.env.TOKEN);

(async () => {
  try {
    await rest.put(
      Routes.applicationGuildCommands(process.env.CLIENT_ID, process.env.GUILD_ID),
      { body: commands }
    );

    console.log("Commandes NxR déployées.");
  } catch (error) {
    console.error("Erreur déploiement commandes :", error);
  }
})();