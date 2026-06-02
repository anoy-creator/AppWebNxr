<?php

namespace App\Entity;

use App\Repository\GameMatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameMatchRepository::class)]
class GameMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $playedAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Team $teamA;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Team $teamB;

    #[ORM\Column(length: 100)]
    private string $game;

    #[ORM\Column(length: 100)]
    private string $mode;

    #[ORM\Column(length: 20)]
    private string $result; // Victory / Defeat

    #[ORM\Column(length: 20)]
    private string $score;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Roster $roster;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayedAt(): \DateTimeImmutable
    {
        return $this->playedAt;
    }

    public function setPlayedAt(\DateTimeImmutable $playedAt): self
    {
        $this->playedAt = $playedAt;

        return $this;
    }

    public function getTeamA(): Team
    {
        return $this->teamA;
    }

    public function setTeamA(Team $teamA): self
    {
        $this->teamA = $teamA;

        return $this;
    }

    public function getTeamB(): Team
    {
        return $this->teamB;
    }

    public function setTeamB(Team $teamB): self
    {
        $this->teamB = $teamB;

        return $this;
    }

    public function getGame(): string
    {
        return $this->game;
    }

    public function setGame(string $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult(string $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getScore(): string
    {
        return $this->score;
    }

    public function setScore(string $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getRoster(): Roster
    {
        return $this->roster;
    }

    public function setRoster(Roster $roster): self
    {
        $this->roster = $roster;

        return $this;
    }
}
