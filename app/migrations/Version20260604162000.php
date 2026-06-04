<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Discord external tournament id to events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Event ADD discordExternalId VARCHAR(64) DEFAULT NULL');
        $this->addSql("ALTER TABLE Event ADD rosterEntries LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("UPDATE Event SET discordExternalId = REGEXP_SUBSTR(description, 'ID bot: [0-9A-Za-z_-]+') WHERE description LIKE '%ID bot:%'");
        $this->addSql("UPDATE Event SET discordExternalId = REPLACE(discordExternalId, 'ID bot: ', '') WHERE discordExternalId IS NOT NULL");
        $this->addSql("UPDATE Event SET description = TRIM(REGEXP_REPLACE(description, 'ID bot: [0-9A-Za-z_-]+', '')) WHERE description LIKE '%ID bot:%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Event DROP discordExternalId');
        $this->addSql('ALTER TABLE Event DROP rosterEntries');
    }
}
