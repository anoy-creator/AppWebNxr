import fs from 'fs';
import path from 'path';

const ADMIN_ROLE_ID = '1409844772419665930';

export default {
  name: 'help',
  description: 'Liste toutes les commandes disponibles',
  async execute(message, args) {
    const member = message.member;
    const hasAdminRole = member.roles.cache.has(ADMIN_ROLE_ID);

    const commandsPath = path.join(process.cwd(), 'cmd');
    const commandFiles = fs.readdirSync(commandsPath).filter(file => file.endsWith('.js') && file !== 'help.js');

    let reply = '📜 **Liste des commandes disponibles :**\n\n';

    for (const file of commandFiles) {
      const filePath = path.join(commandsPath, file);
      const command = await import(filePath);
      const cmd = command.default;

      // Affiche la commande si elle n'est pas cachée ou si l'utilisateur a le rôle admin
      if (!cmd.hidden || hasAdminRole) {
        reply += `**!nxr ${cmd.name}** ${cmd.description}\n`;
      }
    }

    await message.reply(reply);
    await message.delete().catch(() => {});
  },
};
