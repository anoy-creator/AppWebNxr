import fs from 'fs';
import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const ADMIN_ROLE_ID = process.env.ADMIN_ROLE_ID;
const PREFIX = process.env.PREFIX || '!nxr';

export default {
  name: 'help',
  description: 'Liste toutes les commandes disponibles',

  async execute(message) {
    const hasAdminRole =
        ADMIN_ROLE_ID &&
        ADMIN_ROLE_ID !== '#' &&
        message.member?.roles?.cache?.has(ADMIN_ROLE_ID);

    const commandFiles = fs
        .readdirSync(__dirname)
        .filter(file => file.endsWith('.js') && file !== 'help.js');

    let reply = '**Liste des commandes disponibles :**\n\n';
    reply += `**${PREFIX} help** - ${this.description}\n`;

    for (const file of commandFiles) {
      const commandPath = path.join(__dirname, file);
      const { default: cmd } = await import(pathToFileURL(commandPath).href);

      if (cmd.hidden && !hasAdminRole) continue;

      const commandLabel = cmd.slashOnly
          ? `/${cmd.data?.name || cmd.name}`
          : `${PREFIX} ${cmd.name}`;

      reply += `**${commandLabel}** - ${cmd.description || 'Aucune description'}\n`;
    }

    await message.reply(reply);
    await message.delete().catch(() => {});
  },
};
