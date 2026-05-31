<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'UNIQ_discord_id', columns: ['discord_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_username', columns: ['username'])]
#[ORM\Index(name: 'IDX_created_at', columns: ['created_at'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true, name: 'discord_id')]
    private ?string $discordId = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'text', nullable: true, name: 'discord_token')]
    private ?string $discordToken = null;

    #[ORM\Column(type: 'text', nullable: true, name: 'discord_refresh_token')]
    private ?string $discordRefreshToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'last_login_at')]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\OneToMany(targetEntity: MatchPlayer::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $matchPlayers;

    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $teamMembers;

    public function __construct()
    {
        $this->matchPlayers = new ArrayCollection();
        $this->teamMembers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscordId(): ?string
    {
        return $this->discordId;
    }

    public function setDiscordId(string $discordId): static
    {
        $this->discordId = $discordId;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDiscordToken(): ?string
    {
        return $this->discordToken;
    }

    public function setDiscordToken(?string $discordToken): static
    {
        $this->discordToken = $discordToken;

        return $this;
    }

    public function getDiscordRefreshToken(): ?string
    {
        return $this->discordRefreshToken;
    }

    public function setDiscordRefreshToken(?string $discordRefreshToken): static
    {
        $this->discordRefreshToken = $discordRefreshToken;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

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
            $matchPlayer->setUser($this);
        }

        return $this;
    }

    public function removeMatchPlayer(MatchPlayer $matchPlayer): static
    {
        if ($this->matchPlayers->removeElement($matchPlayer)) {
            if ($matchPlayer->getUser() === $this) {
                $matchPlayer->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TeamMember>
     */
    public function getTeamMembers(): Collection
    {
        return $this->teamMembers;
    }

    public function addTeamMember(TeamMember $teamMember): static
    {
        if (!$this->teamMembers->contains($teamMember)) {
            $this->teamMembers->add($teamMember);
            $teamMember->setUser($this);
        }

        return $this;
    }

    public function removeTeamMember(TeamMember $teamMember): static
    {
        if ($this->teamMembers->removeElement($teamMember)) {
            if ($teamMember->getUser() === $this) {
                $teamMember->setUser(null);
            }
        }

        return $this;
    }
}
