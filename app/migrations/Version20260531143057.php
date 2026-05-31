<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260531143057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_maps (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, mode VARCHAR(100) NOT NULL, is_active TINYINT NOT NULL, INDEX IDX_map_name (name), INDEX IDX_map_mode (mode), INDEX IDX_map_is_active (is_active), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE match_players (id INT AUTO_INCREMENT NOT NULL, kills INT NOT NULL, deaths INT NOT NULL, assists INT NOT NULL, score INT NOT NULL, damage INT DEFAULT NULL, objective_score INT DEFAULT NULL, match_id INT NOT NULL, user_id INT NOT NULL, team_id INT NOT NULL, INDEX IDX_match_player_match (match_id), INDEX IDX_match_player_user (user_id), INDEX IDX_match_player_team (team_id), INDEX IDX_match_player_score (score), UNIQUE INDEX UNIQ_match_user_team (match_id, user_id, team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE matches (id INT AUTO_INCREMENT NOT NULL, score_team_a INT NOT NULL, score_team_b INT NOT NULL, played_at DATETIME NOT NULL, created_at DATETIME NOT NULL, season_id INT NOT NULL, game_map_id INT NOT NULL, team_a_id INT NOT NULL, team_b_id INT NOT NULL, winner_team_id INT DEFAULT NULL, INDEX IDX_match_season (season_id), INDEX IDX_match_map (game_map_id), INDEX IDX_match_team_a (team_a_id), INDEX IDX_match_team_b (team_b_id), INDEX IDX_match_winner (winner_team_id), INDEX IDX_match_played_at (played_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE seasons (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_season_is_active (is_active), INDEX IDX_season_start_date (start_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE team_members (id INT AUTO_INCREMENT NOT NULL, joined_at DATETIME NOT NULL, left_at DATETIME DEFAULT NULL, user_id INT NOT NULL, team_id INT NOT NULL, INDEX IDX_team_member_user (user_id), INDEX IDX_team_member_team (team_id), INDEX IDX_team_member_joined (joined_at), INDEX IDX_team_member_left (left_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE teams (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, tag VARCHAR(50) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_team_created_at (created_at), UNIQUE INDEX UNIQ_team_name (name), UNIQUE INDEX UNIQ_team_tag (tag), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, discord_id VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, discord_token LONGTEXT DEFAULT NULL, discord_refresh_token LONGTEXT DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, INDEX IDX_created_at (created_at), UNIQUE INDEX UNIQ_discord_id (discord_id), UNIQUE INDEX UNIQ_email (email), UNIQUE INDEX UNIQ_username (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT FK_51E81CC92ABEACD6 FOREIGN KEY (match_id) REFERENCES matches (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT FK_51E81CC9A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT FK_51E81CC9296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BA4EC001D1 FOREIGN KEY (season_id) REFERENCES seasons (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BA2EF0F2AC FOREIGN KEY (game_map_id) REFERENCES game_maps (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BAEA3FA723 FOREIGN KEY (team_a_id) REFERENCES teams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BAF88A08CD FOREIGN KEY (team_b_id) REFERENCES teams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BAC5237001 FOREIGN KEY (winner_team_id) REFERENCES teams (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_51E81CC92ABEACD6');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_51E81CC9A76ED395');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_51E81CC9296CD8AE');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BA4EC001D1');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BA2EF0F2AC');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BAEA3FA723');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BAF88A08CD');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BAC5237001');
        $this->addSql('ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8A76ED395');
        $this->addSql('ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8296CD8AE');
        $this->addSql('DROP TABLE game_maps');
        $this->addSql('DROP TABLE match_players');
        $this->addSql('DROP TABLE matches');
        $this->addSql('DROP TABLE seasons');
        $this->addSql('DROP TABLE team_members');
        $this->addSql('DROP TABLE teams');
        $this->addSql('DROP TABLE users');
    }
}
