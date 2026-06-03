<?php

namespace App\Entity;

use App\Repository\GameMatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameMatchRepository::class)]
class GameMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $playedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mapName = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $teamA = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $teamB = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Roster $roster = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'matches')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Event $tournament = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Player $captain = null;

    #[ORM\ManyToMany(targetEntity: Player::class)]
    #[ORM\JoinTable(name: 'game_match_players')]
    private Collection $players;

    #[ORM\ManyToMany(targetEntity: Player::class)]
    #[ORM\JoinTable(name: 'game_match_substitutes')]
    private Collection $substitutes;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $opponents = null;

    #[ORM\Column(length: 100)]
    private ?string $game = null;

    #[ORM\Column(length: 100)]
    private ?string $mode = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $score = null;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->substitutes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayedAt(): ?\DateTimeImmutable
    {
        return $this->playedAt;
    }

    public function setPlayedAt(\DateTimeImmutable $playedAt): self
    {
        $this->playedAt = $playedAt;

        return $this;
    }

    public function getMapName(): ?string
    {
        return $this->mapName;
    }

    public function setMapName(?string $mapName): self
    {
        $this->mapName = $mapName;

        return $this;
    }

    public function getTeamA(): ?Team
    {
        return $this->teamA;
    }

    public function setTeamA(?Team $teamA): self
    {
        $this->teamA = $teamA;

        return $this;
    }

    public function getTeamB(): ?Team
    {
        return $this->teamB;
    }

    public function setTeamB(?Team $teamB): self
    {
        $this->teamB = $teamB;

        return $this;
    }

    public function getRoster(): ?Roster
    {
        return $this->roster;
    }

    public function setRoster(?Roster $roster): self
    {
        $this->roster = $roster;

        return $this;
    }

    public function getTournament(): ?Event
    {
        return $this->tournament;
    }

    public function setTournament(?Event $tournament): self
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getCaptain(): ?Player
    {
        return $this->captain;
    }

    public function setCaptain(?Player $captain): self
    {
        $this->captain = $captain;

        return $this;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): self
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
        }

        return $this;
    }

    public function removePlayer(Player $player): self
    {
        $this->players->removeElement($player);

        return $this;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getSubstitutes(): Collection
    {
        return $this->substitutes;
    }

    public function addSubstitute(Player $substitute): self
    {
        if (!$this->substitutes->contains($substitute)) {
            $this->substitutes->add($substitute);
        }

        return $this;
    }

    public function removeSubstitute(Player $substitute): self
    {
        $this->substitutes->removeElement($substitute);

        return $this;
    }

    public function getOpponents(): ?string
    {
        return $this->opponents;
    }

    public function setOpponents(?string $opponents): self
    {
        $this->opponents = $opponents;

        return $this;
    }

    public function getGame(): ?string
    {
        return $this->game;
    }

    public function setGame(string $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(?string $score): self
    {
        $this->score = $score;

        return $this;
    }
}
