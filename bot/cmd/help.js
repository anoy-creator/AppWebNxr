import fs from 'fs';
import path from 'path';
import { pathToFileURL } from 'url';

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

    const commandsPath = path.join(process.cwd(), 'cmd');
    const commandFiles = fs
        .readdirSync(commandsPath)
        .filter(file => file.endsWith('.js') && file !== 'help.js');

    let reply = '**Liste des commandes disponibles :**\n\n';

    for (const file of commandFiles) {
      const commandPath = path.join(commandsPath, file);
      const { default: cmd } = await import(pathToFileURL(commandPath).href);

      if (!cmd.hidden || hasAdminRole) {
        reply += `**${PREFIX} ${cmd.name}** — ${cmd.description || 'Aucune description'}\n`;
      }
    }

    await message.reply(reply);
    await message.delete().catch(() => {});
  },
};