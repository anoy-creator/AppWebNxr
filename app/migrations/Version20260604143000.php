<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Discord tournament checkins to events.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Event ADD checkins LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Event DROP checkins');
    }
}
