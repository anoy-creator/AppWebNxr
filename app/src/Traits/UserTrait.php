<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait UserTrait
{
    #[ORM\Column(type: 'string', length: 50)]
    private ?string $discordId = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $discordName = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $activitonId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function initializeUserTrait(): void
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getDiscordName(): ?string
    {
        return $this->discordName;
    }

    public function setDiscordName(string $discordName): static
    {
        $this->discordName = $discordName;

        return $this;
    }

    public function getActivitonId(): ?string
    {
        return $this->activitonId;
    }

    public function setActivitonId(?string $activitonId): static
    {
        $this->activitonId = $activitonId;

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
}
