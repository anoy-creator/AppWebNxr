<?php

namespace App\Entity;

use App\Repository\PlayerMatchStatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerMatchStatRepository::class)]
class PlayerMatchStat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private GameMatch $match;

    #[ORM\Column]
    private int $kills = 0;

    #[ORM\Column]
    private int $deaths = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getMatch(): GameMatch
    {
        return $this->match;
    }

    public function setMatch(GameMatch $match): self
    {
        $this->match = $match;

        return $this;
    }

    public function getKills(): int
    {
        return $this->kills;
    }

    public function setKills(int $kills): self
    {
        $this->kills = $kills;

        return $this;
    }

    public function getDeaths(): int
    {
        return $this->deaths;
    }

    public function setDeaths(int $deaths): self
    {
        $this->deaths = $deaths;

        return $this;
    }

    public function getKd(): float
    {
        return $this->deaths > 0 ? round($this->kills / $this->deaths, 2) : $this->kills;
    }
}
