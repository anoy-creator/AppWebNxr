<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tournament squads, match squads, opponents and COD map fields.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `event` ADD captain_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_EVENT_CAPTAIN ON `event` (captain_id)');
        $this->addSql('ALTER TABLE `event` ADD CONSTRAINT FK_EVENT_CAPTAIN FOREIGN KEY (captain_id) REFERENCES player (id) ON DELETE SET NULL');

        $this->addSql('CREATE TABLE event_players (event_id INT NOT NULL, player_id INT NOT NULL, INDEX IDX_EVENT_PLAYERS_EVENT (event_id), INDEX IDX_EVENT_PLAYERS_PLAYER (player_id), PRIMARY KEY(event_id, player_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_substitutes (event_id INT NOT NULL, player_id INT NOT NULL, INDEX IDX_EVENT_SUBSTITUTES_EVENT (event_id), INDEX IDX_EVENT_SUBSTITUTES_PLAYER (player_id), PRIMARY KEY(event_id, player_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_players ADD CONSTRAINT FK_EVENT_PLAYERS_EVENT FOREIGN KEY (event_id) REFERENCES `event` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_players ADD CONSTRAINT FK_EVENT_PLAYERS_PLAYER FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_substitutes ADD CONSTRAINT FK_EVENT_SUBSTITUTES_EVENT FOREIGN KEY (event_id) REFERENCES `event` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_substitutes ADD CONSTRAINT FK_EVENT_SUBSTITUTES_PLAYER FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE game_match ADD tournament_id INT DEFAULT NULL, ADD captain_id INT DEFAULT NULL, ADD opponents LONGTEXT DEFAULT NULL, ADD map_name VARCHAR(100) DEFAULT NULL, CHANGE result result VARCHAR(20) DEFAULT NULL, CHANGE score score VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_GAME_MATCH_TOURNAMENT ON game_match (tournament_id)');
        $this->addSql('CREATE INDEX IDX_GAME_MATCH_CAPTAIN ON game_match (captain_id)');
        $this->addSql('ALTER TABLE game_match ADD CONSTRAINT FK_GAME_MATCH_TOURNAMENT FOREIGN KEY (tournament_id) REFERENCES `event` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_match ADD CONSTRAINT FK_GAME_MATCH_CAPTAIN FOREIGN KEY (captain_id) REFERENCES player (id) ON DELETE SET NULL');

        $this->addSql('CREATE TABLE game_match_players (game_match_id INT NOT NULL, player_id INT NOT NULL, INDEX IDX_GAME_MATCH_PLAYERS_MATCH (game_match_id), INDEX IDX_GAME_MATCH_PLAYERS_PLAYER (player_id), PRIMARY KEY(game_match_id, player_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_match_substitutes (game_match_id INT NOT NULL, player_id INT NOT NULL, INDEX IDX_GAME_MATCH_SUBSTITUTES_MATCH (game_match_id), INDEX IDX_GAME_MATCH_SUBSTITUTES_PLAYER (player_id), PRIMARY KEY(game_match_id, player_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game_match_players ADD CONSTRAINT FK_GAME_MATCH_PLAYERS_MATCH FOREIGN KEY (game_match_id) REFERENCES game_match (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_match_players ADD CONSTRAINT FK_GAME_MATCH_PLAYERS_PLAYER FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_match_substitutes ADD CONSTRAINT FK_GAME_MATCH_SUBSTITUTES_MATCH FOREIGN KEY (game_match_id) REFERENCES game_match (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_match_substitutes ADD CONSTRAINT FK_GAME_MATCH_SUBSTITUTES_PLAYER FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_match_substitutes DROP FOREIGN KEY FK_GAME_MATCH_SUBSTITUTES_PLAYER');
        $this->addSql('ALTER TABLE game_match_substitutes DROP FOREIGN KEY FK_GAME_MATCH_SUBSTITUTES_MATCH');
        $this->addSql('ALTER TABLE game_match_players DROP FOREIGN KEY FK_GAME_MATCH_PLAYERS_PLAYER');
        $this->addSql('ALTER TABLE game_match_players DROP FOREIGN KEY FK_GAME_MATCH_PLAYERS_MATCH');
        $this->addSql('ALTER TABLE game_match DROP FOREIGN KEY FK_GAME_MATCH_CAPTAIN');
        $this->addSql('ALTER TABLE game_match DROP FOREIGN KEY FK_GAME_MATCH_TOURNAMENT');
        $this->addSql('ALTER TABLE event_substitutes DROP FOREIGN KEY FK_EVENT_SUBSTITUTES_PLAYER');
        $this->addSql('ALTER TABLE event_substitutes DROP FOREIGN KEY FK_EVENT_SUBSTITUTES_EVENT');
        $this->addSql('ALTER TABLE event_players DROP FOREIGN KEY FK_EVENT_PLAYERS_PLAYER');
        $this->addSql('ALTER TABLE event_players DROP FOREIGN KEY FK_EVENT_PLAYERS_EVENT');
        $this->addSql('ALTER TABLE `event` DROP FOREIGN KEY FK_EVENT_CAPTAIN');

        $this->addSql('DROP TABLE game_match_substitutes');
        $this->addSql('DROP TABLE game_match_players');
        $this->addSql('DROP TABLE event_substitutes');
        $this->addSql('DROP TABLE event_players');

        $this->addSql('DROP INDEX IDX_GAME_MATCH_CAPTAIN ON game_match');
        $this->addSql('DROP INDEX IDX_GAME_MATCH_TOURNAMENT ON game_match');
        $this->addSql("UPDATE game_match SET result = 'Pending' WHERE result IS NULL");
        $this->addSql("UPDATE game_match SET score = '0-0' WHERE score IS NULL");
        $this->addSql('ALTER TABLE game_match DROP tournament_id, DROP captain_id, DROP opponents, DROP map_name, CHANGE result result VARCHAR(20) NOT NULL, CHANGE score score VARCHAR(20) NOT NULL');

        $this->addSql('DROP INDEX IDX_EVENT_CAPTAIN ON `event`');
        $this->addSql('ALTER TABLE `event` DROP captain_id');
    }
}
