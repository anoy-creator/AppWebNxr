import axios from 'axios';
import { SlashCommandBuilder } from 'discord.js';

const EVENT_TYPES = [
    { name: 'Entrainement', value: 'training' },
    { name: 'Reunion', value: 'meeting' },
    { name: 'Tournoi', value: 'tournament' },
    { name: 'Match officiel', value: 'match' },
];

const TOURNAMENT_FORMATS = ['1v1', '2v2', '3v3', '4v4', '5v5', '6v6'];

function extractMentionIds(input) {
    if (!input) return [];
    return [...input.matchAll(/<@!?(\d+)>/g)].map(match => match[1]);
}

function validateDateHeure(date, heure) {
    const regexDate = /^([0-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/\d{4}$/;
    const regexHeure = /^([01][0-9]|2[0-3]):[0-5][0-9]$/;

    if (!regexDate.test(date)) return 'Format de date invalide. Utilise : 10/06/2026';
    if (!regexHeure.test(heure)) return "Format d'heure invalide. Utilise : 21:00";

    return null;
}

function parseDateTime(date, heure) {
    const [day, month, year] = date.split('/').map(Number);
    const [hours, minutes] = heure.split(':').map(Number);

    return new Date(year, month - 1, day, hours, minutes, 0);
}

function hasCaptainRole(interaction) {
    const roleName = process.env.CAPITAINE_ROLE_NAME || 'Capitaine';
    const roleId = process.env.CAPITAINE_ROLE_ID;
    const roles = interaction.member?.roles;

    if (roles?.cache) {
        return roles.cache.some(role =>
            role.name === roleName || (roleId && role.id === roleId),
        );
    }

    if (Array.isArray(roles)) {
        return Boolean(roleId && roles.includes(roleId));
    }

    return false;
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

async function syncEventWithSite(payload) {
    if (!process.env.ROUTE_API || process.env.ROUTE_API === '#' || !process.env.API_KEY || process.env.API_KEY === '#') {
        return {
            success: false,
            message: 'API du site non configuree',
        };
    }

    try {
        const response = await axios.post(
            `${process.env.ROUTE_API}/discord/add-event`,
            payload,
            {
                headers: {
                    'x-api-key': process.env.API_KEY,
                },
            },
        );

        return {
            success: true,
            message: response.data?.message || 'Event cree sur le site',
            event: response.data?.event || null,
        };
    } catch (error) {
        console.error('Erreur synchronisation event site :', error.response?.data || error.message);

        return {
            success: false,
            message: error.response?.data?.message || error.message,
        };
    }
}

async function executeInteraction(interaction) {
    await interaction.deferReply();

    if (!(await checkCommandChannel(interaction))) return;

    if (!hasCaptainRole(interaction)) {
        return interaction.editReply({
            content: 'Tu dois avoir le role Capitaine pour utiliser cette commande.',
        });
    }

    const subcommand = interaction.options.getSubcommand();

    if (subcommand !== 'ajouter') {
        return interaction.editReply({ content: 'Sous-commande inconnue.' });
    }

    const type = interaction.options.getString('type');
    const title = interaction.options.getString('titre');
    const date = interaction.options.getString('date');
    const heure = interaction.options.getString('heure');
    const description = interaction.options.getString('description');
    const format = interaction.options.getString('format');
    const captain = interaction.options.getUser('capitaine');
    const players = extractMentionIds(interaction.options.getString('joueurs'));
    const substitutes = extractMentionIds(interaction.options.getString('remplacants'));

    const validationError = validateDateHeure(date, heure);
    if (validationError) return interaction.editReply({ content: validationError });

    const eventDate = parseDateTime(date, heure);
    if (isNaN(eventDate.getTime())) {
        return interaction.editReply({ content: 'Date invalide. Exemple valide : 10/06/2026 a 21:00' });
    }

    if (type === 'tournament' && !TOURNAMENT_FORMATS.includes(format)) {
        return interaction.editReply({
            content: `Un tournoi doit avoir un format valide : ${TOURNAMENT_FORMATS.join(', ')}`,
        });
    }

    if (['training', 'tournament', 'match'].includes(type) && players.length === 0) {
        return interaction.editReply({
            content: 'Ajoute au moins un joueur pour un entrainement, un tournoi ou un match officiel.',
        });
    }

    const payload = {
        type,
        title,
        date,
        heure,
        description,
        format: type === 'tournament' ? format : null,
        captain: captain?.id || null,
        players,
        substitutes,
        createdBy: interaction.user.id,
        timestamp: eventDate.getTime(),
    };

    const siteSync = await syncEventWithSite(payload);

    if (!siteSync.success) {
        return interaction.editReply({
            content: `Event non synchronise : ${siteSync.message}`,
        });
    }

    return interaction.editReply({
        content:
            '**Event NxR cree sur le site**\n\n' +
            `Type : **${EVENT_TYPES.find(item => item.value === type)?.name || type}**\n` +
            `Titre : **${title}**\n` +
            `Date : **${date} ${heure}**\n` +
            (format ? `Format : **${format}**\n` : '') +
            (captain ? `Capitaine : <@${captain.id}>\n` : '') +
            `Joueurs : ${players.length ? players.map(id => `<@${id}>`).join(', ') : 'Aucun'}\n` +
            `Remplacants : ${substitutes.length ? substitutes.map(id => `<@${id}>`).join(', ') : 'Aucun'}\n` +
            `ID site : \`${siteSync.event?.id || '?'}\``,
    });
}

export default {
    name: 'event',
    description: 'Gestion des events NxR (/event)',
    slashOnly: true,
    data: new SlashCommandBuilder()
        .setName('event')
        .setDescription('Gestion des events NxR')
        .addSubcommand(sub =>
            sub
                .setName('ajouter')
                .setDescription('Ajouter un event sur le site')
                .addStringOption(opt =>
                    opt.setName('type')
                        .setDescription('Type d event')
                        .setRequired(true)
                        .addChoices(...EVENT_TYPES),
                )
                .addStringOption(opt =>
                    opt.setName('titre')
                        .setDescription('Titre de l event')
                        .setRequired(true),
                )
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
                    opt.setName('description')
                        .setDescription('Description')
                        .setRequired(true),
                )
                .addStringOption(opt =>
                    opt.setName('format')
                        .setDescription('Format si tournoi')
                        .setRequired(false)
                        .addChoices(...TOURNAMENT_FORMATS.map(format => ({ name: format, value: format }))),
                )
                .addUserOption(opt =>
                    opt.setName('capitaine')
                        .setDescription('Capitaine / responsable')
                        .setRequired(false),
                )
                .addStringOption(opt =>
                    opt.setName('joueurs')
                        .setDescription('Joueurs : @joueur1 @joueur2')
                        .setRequired(false),
                )
                .addStringOption(opt =>
                    opt.setName('remplacants')
                        .setDescription('Remplacants : @joueur1 @joueur2')
                        .setRequired(false),
                ),
        ),
    executeInteraction,
    async execute(message) {
        await message.reply('Cette commande est disponible en slash : `/event ajouter`.');
    },
};
