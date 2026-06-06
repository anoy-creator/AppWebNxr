<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    public const Entrainement = 'training';
    public const Reunion = 'meeting';
    public const Tournoi = 'tournament';
    public const MatchOfficiel = 'match';
    public const TournamentFormats = ['1v1', '2v2', '3v3', '4v4', '5v5', '6v6'];
    public const TypeLabels = [
        self::Entrainement => 'Entrainement',
        self::Reunion => 'Reunion',
        self::Tournoi => 'Tournoi',
        self::MatchOfficiel => 'Match officiel',
    ];
    public const TypeColors = [
        self::Entrainement => 'blue',
        self::Reunion => 'yellow',
        self::Tournoi => 'purple',
        self::MatchOfficiel => 'red',
    ];
    public const EventTypes = [
        self::Entrainement,
        self::Reunion,
        self::Tournoi,
        self::MatchOfficiel,
    ];
    public const ScheduleTypes = [
        self::Entrainement,
        self::Reunion,
        self::Tournoi,
        self::MatchOfficiel,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $title = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 5)]
    private ?string $time = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $tournamentFormat = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $discordExternalId = null;

    /**
     * @var array<array-key, string>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $checkins = [];

    /**
     * @var list<array<string, string>>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rosterEntries = [];

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Player $captain = null;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\ManyToMany(targetEntity: Player::class)]
    #[ORM\JoinTable(name: 'event_players')]
    private Collection $players;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\ManyToMany(targetEntity: Player::class)]
    #[ORM\JoinTable(name: 'event_substitutes')]
    private Collection $substitutes;

    /**
     * @var Collection<int, GameMatch>
     */
    #[ORM\OneToMany(mappedBy: 'tournament', targetEntity: GameMatch::class)]
    private Collection $matches;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->substitutes = new ArrayCollection();
        $this->matches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(string $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPublicDescription(): string
    {
        $description = trim((string) $this->description);
        $description = preg_replace('/\s*ID bot:\s*\S+/i', '', $description) ?? $description;

        return trim($description);
    }

    public function getTournamentFormat(): ?string
    {
        return $this->tournamentFormat;
    }

    public function setTournamentFormat(?string $tournamentFormat): self
    {
        $this->tournamentFormat = $tournamentFormat;

        return $this;
    }

    public function getDiscordExternalId(): ?string
    {
        return $this->discordExternalId;
    }

    public function setDiscordExternalId(?string $discordExternalId): self
    {
        $discordExternalId = trim((string) ($discordExternalId ?? ''));
        $this->discordExternalId = '' === $discordExternalId ? null : $discordExternalId;

        return $this;
    }

    /**
     * @return array<array-key, string>
     */
    public function getCheckins(): array
    {
        return $this->checkins ?? [];
    }

    /**
     * @param array<array-key, string> $checkins
     */
    public function setCheckins(array $checkins): self
    {
        $this->checkins = $checkins;

        return $this;
    }

    public function setCheckin(string $discordId, string $status): self
    {
        $checkins = $this->getCheckins();
        $checkins[$discordId] = $status;
        $this->checkins = $checkins;

        return $this;
    }

    public function getCheckinForPlayer(?Player $player): ?string
    {
        $discordId = $player?->getDiscordId();

        if (!$discordId) {
            return null;
        }

        return $this->getCheckins()[$discordId] ?? null;
    }

    /**
     * @return list<array<string, string>>
     */
    public function getRosterEntries(): array
    {
        return $this->rosterEntries ?? [];
    }

    /**
     * @param list<array<string, string>> $rosterEntries
     */
    public function setRosterEntries(array $rosterEntries): self
    {
        $this->rosterEntries = $rosterEntries;

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

    /**
     * @return Collection<int, GameMatch>
     */
    public function getMatches(): Collection
    {
        return $this->matches;
    }

    public function addMatch(GameMatch $match): self
    {
        if (!$this->matches->contains($match)) {
            $this->matches->add($match);
            $match->setTournament($this);
        }

        return $this;
    }

    public function removeMatch(GameMatch $match): self
    {
        if ($this->matches->removeElement($match)) {
            if ($match->getTournament() === $this) {
                $match->setTournament(null);
            }
        }

        return $this;
    }
}
