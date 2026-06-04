import axios from 'axios';
import { SlashCommandBuilder } from 'discord.js';

const ROUTE_API = process.env.ROUTE_API;
const API_KEY = process.env.API_KEY;

function getMemberRoles(interaction) {
    const roles = interaction.member?.roles;

    if (!roles) return [];

    if (roles.cache) {
        return roles.cache
            .filter(role => role.name !== '@everyone')
            .map(role => ({
                id: role.id,
                name: role.name,
            }));
    }

    if (Array.isArray(roles)) {
        return roles
            .map(roleId => interaction.guild?.roles.cache.get(roleId))
            .filter(role => role && role.name !== '@everyone')
            .map(role => ({
                id: role.id,
                name: role.name,
            }));
    }

    return [];
}

function getDisplayName(interaction) {
    return interaction.member?.displayName
        || interaction.member?.nick
        || interaction.user.globalName
        || interaction.user.username;
}

async function executeInteraction(interaction) {
    if (!ROUTE_API || ROUTE_API === '#') {
        return interaction.reply({
            content: 'API non configuree.',
            ephemeral: true,
        });
    }

    await interaction.deferReply({ ephemeral: true });

    try {
        const payload = {
            discordId: interaction.user.id,
            username: interaction.user.username,
            displayName: getDisplayName(interaction),
            avatar: interaction.user.displayAvatarURL(),
            guildId: interaction.guildId,
            guildName: interaction.guild?.name || 'Discord',
            roles: getMemberRoles(interaction),
        };

        const response = await axios.post(
            `${ROUTE_API}/discord/register`,
            payload,
            {
                headers: {
                    'x-api-key': API_KEY,
                },
            },
        );

        return interaction.editReply({
            content: `Compte synchronise.\n${response.data.message || ''}`,
        });
    } catch (error) {
        console.error(error);

        return interaction.editReply({
            content: `Erreur API : ${error.response?.data?.message || error.message}`,
        });
    }
}

export default {
    name: 'register',
    description: 'Enregistre le compte Discord sur le site',
    data: new SlashCommandBuilder()
        .setName('register')
        .setDescription('Enregistre ton compte Discord sur le site'),
    executeInteraction,
};
