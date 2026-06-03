<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DiscordAccountLinker
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function syncUserFromDiscord(array $data, bool $flush = true): User
    {
        $discordId = $this->readRequiredString($data, 'discordId');
        $username = $this->readString($data, 'username') ?? $discordId;
        $displayName = $this->readString($data, 'displayName') ?? $username;
        $avatar = $this->readString($data, 'avatar');

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['discordId' => $discordId]) ?? new User();

        $user
            ->setDiscordId($discordId)
            ->setDiscordName($displayName)
            ->setUsername($username)
            ->setAvatar($avatar)
            ->setLastLoginAt(new \DateTimeImmutable());

        $player = $this->findOrCreatePlayer($discordId, $displayName, $avatar);
        $user->setPlayer($player);

        $this->entityManager->persist($player);
        $this->entityManager->persist($user);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $user;
    }

    public function findOrCreatePlayerByDiscordId(
        string $discordId,
        ?string $pseudo = null,
        ?string $avatar = null,
        bool $flush = false,
    ): Player {
        $player = $this->findOrCreatePlayer($discordId, $pseudo ?? $discordId, $avatar);
        $this->entityManager->persist($player);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $player;
    }

    private function findOrCreatePlayer(string $discordId, string $pseudo, ?string $avatar): Player
    {
        $playerRepository = $this->entityManager->getRepository(Player::class);
        $player = $playerRepository->findOneBy(['discordId' => $discordId]);

        if (!$player) {
            $player = $this->findPlayerBySocialDiscordId($discordId);
        }

        $isNewPlayer = false;

        if (!$player) {
            $player = new Player();
            $isNewPlayer = true;
            $player
                ->setPseudo($pseudo)
                ->setRole('Joueur')
                ->setGrade('Membre')
                ->setGame('Call of Duty')
                ->setSocials(['discord' => $discordId]);
        }

        $socials = $player->getSocials();
        $socials['discord'] = $discordId;

        $player
            ->setDiscordId($discordId)
            ->setAvatar($isNewPlayer ? ($avatar ?? '') : ($player->getAvatar() ?: ($avatar ?? '')))
            ->setSocials($socials);

        return $player;
    }

    private function findPlayerBySocialDiscordId(string $discordId): ?Player
    {
        foreach ($this->entityManager->getRepository(Player::class)->findAll() as $player) {
            if (($player->getSocials()['discord'] ?? null) === $discordId) {
                return $player;
            }
        }

        return null;
    }

    private function readRequiredString(array $data, string $key): string
    {
        $value = $this->readString($data, $key);

        if (null === $value) {
            throw new \InvalidArgumentException(sprintf('%s est obligatoire', $key));
        }

        return $value;
    }

    private function readString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (null === $value || '' === $value) {
            return null;
        }

        return (string) $value;
    }
}
