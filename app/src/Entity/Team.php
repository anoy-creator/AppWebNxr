<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: 'teams')]
#[ORM\UniqueConstraint(name: 'UNIQ_team_name', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'UNIQ_team_tag', columns: ['tag'])]
#[ORM\Index(name: 'IDX_team_created_at', columns: ['created_at'])]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true, nullable: true)]
    private ?string $tag = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'team', cascade: ['remove'])]
    private Collection $members;

    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'teamA', cascade: ['remove'])]
    private Collection $gamesAsTeamA;

    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'teamB', cascade: ['remove'])]
    private Collection $gamesAsTeamB;

    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'winnerTeam', cascade: ['remove'])]
    private Collection $wonGames;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->gamesAsTeamA = new ArrayCollection();
        $this->gamesAsTeamB = new ArrayCollection();
        $this->wonGames = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    /**
     * @return Collection<int, TeamMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(TeamMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setTeam($this);
        }

        return $this;
    }

    public function removeMember(TeamMember $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getTeam() === $this) {
                $member->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsTeamA(): Collection
    {
        return $this->gamesAsTeamA;
    }

    public function addGameAsTeamA(Game $game): static
    {
        if (!$this->gamesAsTeamA->contains($game)) {
            $this->gamesAsTeamA->add($game);
            $game->setTeamA($this);
        }

        return $this;
    }

    public function removeGameAsTeamA(Game $game): static
    {
        if ($this->gamesAsTeamA->removeElement($game)) {
            if ($game->getTeamA() === $this) {
                $game->setTeamA(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsTeamB(): Collection
    {
        return $this->gamesAsTeamB;
    }

    public function addGameAsTeamB(Game $game): static
    {
        if (!$this->gamesAsTeamB->contains($game)) {
            $this->gamesAsTeamB->add($game);
            $game->setTeamB($this);
        }

        return $this;
    }

    public function removeGameAsTeamB(Game $game): static
    {
        if ($this->gamesAsTeamB->removeElement($game)) {
            if ($game->getTeamB() === $this) {
                $game->setTeamB(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getWonGames(): Collection
    {
        return $this->wonGames;
    }

    public function addWonGame(Game $game): static
    {
        if (!$this->wonGames->contains($game)) {
            $this->wonGames->add($game);
            $game->setWinnerTeam($this);
        }

        return $this;
    }

    public function removeWonGame(Game $game): static
    {
        if ($this->wonGames->removeElement($game)) {
            if ($game->getWinnerTeam() === $this) {
                $game->setWinnerTeam(null);
            }
        }

        return $this;
    }
}
