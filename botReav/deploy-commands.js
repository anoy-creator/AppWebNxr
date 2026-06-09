require("dotenv").config();

const { REST, Routes, SlashCommandBuilder } = require("discord.js");

const commands = [
    new SlashCommandBuilder()
        .setName("tournoi")
        .setDescription("Gestion des tournois NxR")

        .addSubcommand(sub =>
            sub
                .setName("proposition")
                .setDescription("Proposer un tournoi aux Forces d'élite")
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
                        .setDescription("Format : 3v3, 4v4, 5v5...")
                        .setRequired(true)
                )
        )

        .addSubcommand(sub =>
            sub
                .setName("annuler-proposition")
                .setDescription("Annuler une proposition de tournoi")
                .addStringOption(opt =>
                    opt.setName("id")
                        .setDescription("ID de la proposition")
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
                .setName("valider-proposition")
                .setDescription("Créer un tournoi depuis une proposition")
                .addStringOption(opt =>
                    opt.setName("id")
                        .setDescription("ID de la proposition")
                        .setRequired(true)
                )
                .addUserOption(opt =>
                    opt.setName("capitaine")
                        .setDescription("Capitaine du tournoi")
                        .setRequired(true)
                )
        )

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
                        .setDescription("Format : 3v3, 4v4, 5v5...")
                        .setRequired(true)
                )
                .addUserOption(opt =>
                    opt.setName("capitaine")
                        .setDescription("Capitaine du tournoi")
                        .setRequired(true)
                )
                .addUserOption(opt =>
                    opt.setName("joueur1")
                        .setDescription("Joueur titulaire 1")
                        .setRequired(true)
                )
                .addUserOption(opt =>
                    opt.setName("joueur2")
                        .setDescription("Joueur titulaire 2")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("joueur3")
                        .setDescription("Joueur titulaire 3")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("joueur4")
                        .setDescription("Joueur titulaire 4")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("joueur5")
                        .setDescription("Joueur titulaire 5")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("remplacant1")
                        .setDescription("Remplaçant 1")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("remplacant2")
                        .setDescription("Remplaçant 2")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("remplacant3")
                        .setDescription("Remplaçant 3")
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
                .setName("terminer")
                .setDescription("Marquer un tournoi comme terminé")
                .addStringOption(opt =>
                    opt.setName("id")
                        .setDescription("ID du tournoi")
                        .setRequired(true)
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
                .addUserOption(opt =>
                    opt.setName("joueur1")
                        .setDescription("Nouveau joueur titulaire 1")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("joueur2")
                        .setDescription("Nouveau joueur titulaire 2")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("joueur3")
                        .setDescription("Nouveau joueur titulaire 3")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("joueur4")
                        .setDescription("Nouveau joueur titulaire 4")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("joueur5")
                        .setDescription("Nouveau joueur titulaire 5")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("remplacant1")
                        .setDescription("Nouveau remplaçant 1")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("remplacant2")
                        .setDescription("Nouveau remplaçant 2")
                        .setRequired(false)
                )
                .addUserOption(opt =>
                    opt.setName("remplacant3")
                        .setDescription("Nouveau remplaçant 3")
                        .setRequired(false)
                )
        )
].map(cmd => cmd.toJSON());

console.log(JSON.stringify(commands, null, 2));

const rest = new REST({ version: "10" }).setToken(process.env.TOKEN);

(async () => {
    try {
        await rest.put(
            Routes.applicationGuildCommands(
                process.env.CLIENT_ID,
                process.env.GUILD_ID
            ),
            { body: commands }
        );

        console.log("Commandes NxR déployées.");
    } catch (error) {
        console.error("Erreur déploiement commandes :", error);
    }
})();

