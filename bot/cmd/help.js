import fs from 'fs';
import path from 'path';
import { SlashCommandBuilder } from 'discord.js';
import { fileURLToPath, pathToFileURL } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const ADMIN_ROLE_ID = process.env.ADMIN_ROLE_ID;

function hasAdminRole(interaction) {
    const roles = interaction.member?.roles;

    if (!ADMIN_ROLE_ID || ADMIN_ROLE_ID === '#' || !roles) return false;

    if (roles.cache) return roles.cache.has(ADMIN_ROLE_ID);
    if (Array.isArray(roles)) return roles.includes(ADMIN_ROLE_ID);

    return false;
}

async function executeInteraction(interaction) {
    const commandFiles = fs
        .readdirSync(__dirname)
        .filter(file => file.endsWith('.js'));

    let reply = '**Commandes disponibles :**\n\n';
    const isAdmin = hasAdminRole(interaction);

    for (const file of commandFiles) {
        const commandPath = path.join(__dirname, file);
        const { default: cmd } = await import(pathToFileURL(commandPath).href);

        if (!cmd.data?.name) continue;
        if (cmd.hidden && !isAdmin) continue;

        reply += `**/${cmd.data.name}** - ${cmd.description || 'Aucune description'}\n`;
    }

    return interaction.reply({
        content: reply,
        ephemeral: true,
    });
}

export default {
    name: 'help',
    description: 'Liste les commandes disponibles',
    data: new SlashCommandBuilder()
        .setName('help')
        .setDescription('Liste les commandes disponibles'),
    executeInteraction,
};
