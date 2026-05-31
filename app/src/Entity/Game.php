<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'matches')]
#[ORM\Index(name: 'IDX_match_season', columns: ['season_id'])]
#[ORM\Index(name: 'IDX_match_map', columns: ['game_map_id'])]
#[ORM\Index(name: 'IDX_match_team_a', columns: ['team_a_id'])]
#[ORM\Index(name: 'IDX_match_team_b', columns: ['team_b_id'])]
#[ORM\Index(name: 'IDX_match_winner', columns: ['winner_team_id'])]
#[ORM\Index(name: 'IDX_match_played_at', columns: ['played_at'])]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', name: 'score_team_a')]
    private ?int $scoreTeamA = null;

    #[ORM\Column(type: 'integer', name: 'score_team_b')]
    private ?int $scoreTeamB = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'played_at')]
    private ?\DateTimeImmutable $playedAt = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'games')]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Season $season = null;

    #[ORM\ManyToOne(targetEntity: GameMap::class, inversedBy: 'games')]
    #[ORM\JoinColumn(name: 'game_map_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?GameMap $gameMap = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'gamesAsTeamA')]
    #[ORM\JoinColumn(name: 'team_a_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Team $teamA = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'gamesAsTeamB')]
    #[ORM\JoinColumn(name: 'team_b_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Team $teamB = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'wonGames')]
    #[ORM\JoinColumn(name: 'winner_team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Team $winnerTeam = null;

    #[ORM\OneToMany(targetEntity: MatchPlayer::class, mappedBy: 'game', cascade: ['remove'])]
    private Collection $matchPlayers;

    public function __construct()
    {
        $this->matchPlayers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScoreTeamA(): ?int
    {
        return $this->scoreTeamA;
    }

    public function setScoreTeamA(int $scoreTeamA): static
    {
        $this->scoreTeamA = $scoreTeamA;

        return $this;
    }

    public function getScoreTeamB(): ?int
    {
        return $this->scoreTeamB;
    }

    public function setScoreTeamB(int $scoreTeamB): static
    {
        $this->scoreTeamB = $scoreTeamB;

        return $this;
    }

    public function getPlayedAt(): ?\DateTimeImmutable
    {
        return $this->playedAt;
    }

    public function setPlayedAt(\DateTimeImmutable $playedAt): static
    {
        $this->playedAt = $playedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    public function getGameMap(): ?GameMap
    {
        return $this->gameMap;
    }

    public function setGameMap(?GameMap $gameMap): static
    {
        $this->gameMap = $gameMap;

        return $this;
    }

    public function getTeamA(): ?Team
    {
        return $this->teamA;
    }

    public function setTeamA(?Team $teamA): static
    {
        $this->teamA = $teamA;

        return $this;
    }

    public function getTeamB(): ?Team
    {
        return $this->teamB;
    }

    public function setTeamB(?Team $teamB): static
    {
        $this->teamB = $teamB;

        return $this;
    }

    public function getWinnerTeam(): ?Team
    {
        return $this->winnerTeam;
    }

    public function setWinnerTeam(?Team $winnerTeam): static
    {
        $this->winnerTeam = $winnerTeam;

        return $this;
    }

    /**
     * @return Collection<int, MatchPlayer>
     */
    public function getMatchPlayers(): Collection
    {
        return $this->matchPlayers;
    }

    public function addMatchPlayer(MatchPlayer $matchPlayer): static
    {
        if (!$this->matchPlayers->contains($matchPlayer)) {
            $this->matchPlayers->add($matchPlayer);
            $matchPlayer->setGame($this);
        }

        return $this;
    }

    public function removeMatchPlayer(MatchPlayer $matchPlayer): static
    {
        if ($this->matchPlayers->removeElement($matchPlayer)) {
            if ($matchPlayer->getGame() === $this) {
                $matchPlayer->setGame(null);
            }
        }

        return $this;
    }
}
