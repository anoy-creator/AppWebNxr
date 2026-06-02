import axios from 'axios';

const ROUTE_API = process.env.ROUTE_API;
const API_KEY = process.env.API_KEY;

export default {
    name: 'register',
    description: 'Enregistre le compte Discord sur le site',

    async execute(message) {
        if (!ROUTE_API || ROUTE_API === '#') {
            return message.reply('❌ API non configurée.');
        }

        try {
            const roles = message.member.roles.cache
                .filter(role => role.name !== '@everyone')
                .map(role => ({
                    id: role.id,
                    name: role.name,
                }));

            const payload = {
                discordId: message.author.id,
                username: message.author.username,
                displayName: message.member.displayName,
                avatar: message.author.displayAvatarURL(),
                guildId: message.guild.id,
                guildName: message.guild.name,
                roles,
            };

            const response = await axios.post(
                `${ROUTE_API}/discord/register`,
                payload,
                {
                    headers: {
                        'x-api-key': API_KEY,
                    },
                }
            );

            await message.reply(
                `✅ Compte synchronisé.\n${response.data.message || ''}`
            );
        } catch (error) {
            console.error(error);

            await message.reply(
                `❌ Erreur API : ${error.response?.data?.message || error.message}`
            );
        }
    },
};