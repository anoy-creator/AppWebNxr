import 'dotenv/config';
import { Client, GatewayIntentBits, Collection } from 'discord.js';
import fs from 'fs';
import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const TOKEN = process.env.TOKEN;
const PREFIX = process.env.PREFIX || '!nxr';

if (!TOKEN || TOKEN === '#') {
    console.error('TOKEN manquant ou invalide dans le .env');
    process.exit(1);
}

const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMessages,
        GatewayIntentBits.MessageContent,
        GatewayIntentBits.GuildMembers,
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

    if (!command?.name || !command?.execute) {
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

client.once('ready', () => {
    console.log(`Bot connecte : ${client.user.tag}`);
    console.log(`Prefixe : ${PREFIX}`);
});

client.on('messageCreate', async message => {
    if (message.author.bot) return;
    if (!message.guild) return;
    if (!message.content.startsWith(PREFIX)) return;

    const args = message.content.slice(PREFIX.length).trim().split(/ +/);
    const commandName = args.shift()?.toLowerCase();

    if (!commandName) return;

    const command = client.commands.get(commandName);
    if (!command) return;

    try {
        await command.execute(message, args, client);
    } catch (error) {
        console.error(error);
        await message.reply('Une erreur est survenue.');
    }
});

client.on('interactionCreate', async interaction => {
    try {
        if (interaction.isButton()) {
            for (const command of client.slashCommands.values()) {
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
