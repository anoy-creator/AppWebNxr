<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Player;
use App\Entity\User;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProfileDataEraser
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
    ) {
    }

    public function erase(User $user): void
    {
        $discordId = trim((string) $user->getDiscordId());
        $player = $user->getPlayer();

        if ($player instanceof Player) {
            $user->setPlayer(null);
            $player->setUser(null);
            $this->anonymizePlayer($player);
        }

        if ('' !== $discordId) {
            $this->eraseDiscordDataFromEvents($discordId);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function anonymizePlayer(Player $player): void
    {
        $player
            ->setPseudo('Profil supprime')
            ->setAvatar('')
            ->setRole('Ancien membre')
            ->setGrade('Profil supprime')
            ->setGame('Non renseigne')
            ->setDiscordId(null)
            ->setSocials([]);
    }

    private function eraseDiscordDataFromEvents(string $discordId): void
    {
        foreach ($this->eventRepository->findAll() as $event) {
            if (!$event instanceof Event) {
                continue;
            }

            $this->eraseCheckin($event, $discordId);
            $this->eraseRosterEntry($event, $discordId);
        }
    }

    private function eraseCheckin(Event $event, string $discordId): void
    {
        $checkins = $event->getCheckins();

        if (!array_key_exists($discordId, $checkins)) {
            return;
        }

        unset($checkins[$discordId]);
        $event->setCheckins($checkins);
    }

    private function eraseRosterEntry(Event $event, string $discordId): void
    {
        $rosterEntries = $event->getRosterEntries();
        $filteredRosterEntries = array_values(array_filter(
            $rosterEntries,
            static fn (array $entry): bool => (string) ($entry['discordId'] ?? '') !== $discordId,
        ));

        if ($filteredRosterEntries === $rosterEntries) {
            return;
        }

        $event->setRosterEntries($filteredRosterEntries);
    }
}
