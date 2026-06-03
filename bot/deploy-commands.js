import 'dotenv/config';
import { REST, Routes } from 'discord.js';
import fs from 'fs';
import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const commandsPath = path.join(__dirname, 'cmd');
const commandFiles = fs
    .readdirSync(commandsPath)
    .filter(file => file.endsWith('.js'));

const commands = [];

for (const file of commandFiles) {
    const commandPath = path.join(commandsPath, file);
    const { default: command } = await import(pathToFileURL(commandPath).href);

    if (command.data) {
        commands.push(command.data.toJSON());
    }
}

if (!process.env.TOKEN || !process.env.CLIENT_ID) {
    console.error('TOKEN et CLIENT_ID sont obligatoires pour deployer des commandes slash.');
    process.exit(1);
}

const rest = new REST({ version: '10' }).setToken(process.env.TOKEN);
const guildId = process.env.GUILD_ID || process.env.DISCORD_GUILD_ID || process.env.SERVER_ID;

try {
    if (guildId) {
        await rest.put(
            Routes.applicationGuildCommands(process.env.CLIENT_ID, guildId),
            { body: commands },
        );

        console.log('Commandes NxR deployees sur le serveur.');
    } else {
        await rest.put(
            Routes.applicationCommands(process.env.CLIENT_ID),
            { body: commands },
        );

        console.log('Commandes NxR deployees en global. Ajoute GUILD_ID dans le .env pour un deploiement instantane sur un serveur.');
    }
} catch (error) {
    console.error('Erreur deploiement commandes :', error);
}
