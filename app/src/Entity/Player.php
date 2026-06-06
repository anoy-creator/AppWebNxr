<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $pseudo;

    #[ORM\Column(length: 255)]
    private string $avatar;

    #[ORM\Column(length: 50)]
    private string $role;

    #[ORM\Column(length: 50)]
    private string $grade;

    #[ORM\Column(length: 100)]
    private string $game;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $socials = [];

    #[ORM\ManyToOne(inversedBy: 'players')]
    private ?Roster $roster = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $discordId = null;

    #[ORM\OneToOne(mappedBy: 'player', targetEntity: User::class)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getGrade(): string
    {
        return $this->grade;
    }

    public function setGrade(string $grade): self
    {
        $this->grade = $grade;

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

    /**
     * @return array<string, mixed>
     */
    public function getSocials(): array
    {
        return $this->socials;
    }

    /**
     * @param array<string, mixed> $socials
     */
    public function setSocials(array $socials): self
    {
        $this->socials = $socials;

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

    public function getDiscordId(): ?string
    {
        return $this->discordId;
    }

    public function setDiscordId(?string $discordId): self
    {
        $this->discordId = $discordId;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        if (null !== $user && $user->getPlayer() !== $this) {
            $user->setPlayer($this);
        }

        return $this;
    }
}
