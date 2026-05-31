<?php

namespace App\Entity;

use App\Repository\MatchPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatchPlayerRepository::class)]
#[ORM\Table(name: 'match_players')]
#[ORM\UniqueConstraint(name: 'UNIQ_match_user_team', columns: ['match_id', 'user_id', 'team_id'])]
#[ORM\Index(name: 'IDX_match_player_match', columns: ['match_id'])]
#[ORM\Index(name: 'IDX_match_player_user', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_match_player_team', columns: ['team_id'])]
#[ORM\Index(name: 'IDX_match_player_score', columns: ['score'])]
class MatchPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $kills = null;

    #[ORM\Column(type: 'integer')]
    private ?int $deaths = null;

    #[ORM\Column(type: 'integer')]
    private ?int $assists = null;

    #[ORM\Column(type: 'integer')]
    private ?int $score = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $damage = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'objective_score')]
    private ?int $objectiveScore = null;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'matchPlayers')]
    #[ORM\JoinColumn(name: 'match_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Game $game = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'matchPlayers')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Team $team = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKills(): ?int
    {
        return $this->kills;
    }

    public function setKills(int $kills): static
    {
        $this->kills = $kills;

        return $this;
    }

    public function getDeaths(): ?int
    {
        return $this->deaths;
    }

    public function setDeaths(int $deaths): static
    {
        $this->deaths = $deaths;

        return $this;
    }

    public function getAssists(): ?int
    {
        return $this->assists;
    }

    public function setAssists(int $assists): static
    {
        $this->assists = $assists;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getDamage(): ?int
    {
        return $this->damage;
    }

    public function setDamage(?int $damage): static
    {
        $this->damage = $damage;

        return $this;
    }

    public function getObjectiveScore(): ?int
    {
        return $this->objectiveScore;
    }

    public function setObjectiveScore(?int $objectiveScore): static
    {
        $this->objectiveScore = $objectiveScore;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    /**
     * Calculate KD ratio (Kills/Deaths)
     */
    public function getKdRatio(): float
    {
        if ($this->deaths === 0) {
            return $this->kills > 0 ? (float) $this->kills : 0.0;
        }

        return round($this->kills / $this->deaths, 2);
    }
}
