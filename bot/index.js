import 'dotenv/config';
import { Client, GatewayIntentBits, Collection, Events } from 'discord.js';
import fs from 'fs';
import http from 'http';
import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const TOKEN = process.env.TOKEN;

if (!TOKEN || TOKEN === '#') {
    console.error('TOKEN manquant ou invalide dans le .env');
    process.exit(1);
}

const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
    ],
});

client.commands = new Collection();
client.slashCommands = new Collection();

const commandsPath = path.join(__dirname, 'cmd');
const commandFiles = fs
    .readdirSync(commandsPath)
    .filter(file => file.endsWith('.js'));

for (const file of commandFiles) {
    const commandPath = path.join(commandsPath, file);
    const { default: command } = await import(pathToFileURL(commandPath).href);

    if (!command?.name || (!command.execute && !command.executeInteraction)) {
        console.warn(`Commande ignoree : ${file}`);
        continue;
    }

    client.commands.set(command.name, command);

    if (command.data?.name && command.executeInteraction) {
        client.slashCommands.set(command.data.name, command);
    }

    if (command.setup) {
        command.setup(client);
    }
}

client.once(Events.ClientReady, () => {
    console.log(`Bot connecte : ${client.user.tag}`);
    console.log('Mode commandes slash uniquement');
    startSiteWebhookServer();
});

function readJsonBody(request) {
    return new Promise((resolve, reject) => {
        let body = '';

        request.on('data', chunk => {
            body += chunk;

            if (body.length > 1024 * 1024) {
                reject(new Error('Payload trop volumineux'));
                request.destroy();
            }
        });

        request.on('end', () => {
            try {
                resolve(body ? JSON.parse(body) : {});
            } catch (error) {
                reject(error);
            }
        });
    });
}

function sendJson(response, statusCode, payload) {
    response.writeHead(statusCode, {
        'Content-Type': 'application/json',
    });
    response.end(JSON.stringify(payload));
}

function startSiteWebhookServer() {
    const port = Number(process.env.BOT_WEBHOOK_PORT || 3010);

    const server = http.createServer(async (request, response) => {
        if (request.method !== 'POST' || request.url !== '/site/tournament-updated') {
            sendJson(response, 404, { success: false, message: 'Route introuvable' });
            return;
        }

        if (process.env.API_KEY && request.headers['x-api-key'] !== process.env.API_KEY) {
            sendJson(response, 401, { success: false, message: 'API key invalide' });
            return;
        }

        try {
            const payload = await readJsonBody(request);
            const tournoiCommand = client.commands.get('tournoi');

            if (!tournoiCommand?.handleSiteTournamentUpdate) {
                sendJson(response, 500, { success: false, message: 'Handler tournoi indisponible' });
                return;
            }

            const tournoi = await tournoiCommand.handleSiteTournamentUpdate(client, payload);
            sendJson(response, 200, {
                success: true,
                message: 'Tournoi bot mis a jour',
                id: tournoi.id,
            });
        } catch (error) {
            console.error('Erreur webhook site:', error);
            sendJson(response, 500, { success: false, message: error.message || 'Erreur webhook' });
        }
    });

    server.listen(port, '0.0.0.0', () => {
        console.log(`Webhook site ecoute sur le port ${port}`);
    });

    server.on('error', error => {
        console.error('Impossible de demarrer le webhook site:', error.message);
    });
}

client.on('interactionCreate', async interaction => {
    try {
        if (interaction.isButton()) {
            for (const command of client.commands.values()) {
                if (command.handleButton && await command.handleButton(interaction, client)) {
                    return;
                }
            }

            return;
        }

        if (!interaction.isChatInputCommand()) return;

        const command = client.slashCommands.get(interaction.commandName);
        if (!command) return;

        await command.executeInteraction(interaction, client);
    } catch (error) {
        console.error(error);

        if (interaction.deferred || interaction.replied) {
            await interaction.editReply({ content: 'Une erreur est survenue.' }).catch(() => {});
            return;
        }

        await interaction.reply({
            content: 'Une erreur est survenue.',
            ephemeral: true,
        }).catch(() => {});
    }
});

client.login(TOKEN);
