require("dotenv").config();

const { REST, Routes } = require("discord.js");

const rest = new REST({ version: "10" }).setToken(process.env.TOKEN);

(async () => {
  try {
    await rest.put(
      Routes.applicationGuildCommands(
        process.env.CLIENT_ID,
        process.env.GUILD_ID
      ),
      { body: [] }
    );

    console.log("Toutes les commandes ont été supprimées.");
  } catch (error) {
    console.error(error);
  }
})();