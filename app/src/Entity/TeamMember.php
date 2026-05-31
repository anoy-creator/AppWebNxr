<?php

namespace App\Entity;

use App\Repository\TeamMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamMemberRepository::class)]
#[ORM\Table(name: 'team_members')]
#[ORM\Index(name: 'IDX_team_member_user', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_team_member_team', columns: ['team_id'])]
#[ORM\Index(name: 'IDX_team_member_joined', columns: ['joined_at'])]
#[ORM\Index(name: 'IDX_team_member_left', columns: ['left_at'])]
class TeamMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'joined_at')]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'left_at')]
    private ?\DateTimeImmutable $leftAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'members')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Team $team = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getLeftAt(): ?\DateTimeImmutable
    {
        return $this->leftAt;
    }

    public function setLeftAt(?\DateTimeImmutable $leftAt): static
    {
        $this->leftAt = $leftAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->leftAt === null;
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
}
