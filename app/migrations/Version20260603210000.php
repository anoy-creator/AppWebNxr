<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link users and players through Discord IDs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD discord_id VARCHAR(50) DEFAULT NULL');

        $this->addSql('ALTER TABLE `user` ADD player_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_PLAYER_ID ON `user` (player_id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_USER_PLAYER FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE SET NULL');

        $this->addSql("UPDATE player SET discord_id = JSON_UNQUOTE(JSON_EXTRACT(socials, '$.discord')) WHERE JSON_EXTRACT(socials, '$.discord') IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(socials, '$.discord')) <> ''");
        $this->addSql('UPDATE `user` u INNER JOIN player p ON p.discord_id = u.discord_id SET u.player_id = p.id WHERE u.player_id IS NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PLAYER_DISCORD_ID ON player (discord_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_USER_PLAYER');
        $this->addSql('DROP INDEX UNIQ_USER_PLAYER_ID ON `user`');
        $this->addSql('ALTER TABLE `user` DROP player_id');

        $this->addSql('DROP INDEX UNIQ_PLAYER_DISCORD_ID ON player');
        $this->addSql('ALTER TABLE player DROP discord_id');
    }
}
