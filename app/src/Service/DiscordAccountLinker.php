<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DiscordAccountLinker
{
    private const DefaultDiscordAdminRoleId = '1511390117384949996';

    /**
     * @var array<string, Player>
     */
    private array $playersByDiscordId = [];

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function syncUserFromDiscord(array $data, bool $flush = true): User
    {
        $discordId = $this->readRequiredString($data, 'discordId');
        $username = $this->readString($data, 'username') ?? $discordId;
        $displayName = $this->readString($data, 'displayName') ?? $username;
        $avatar = $this->readString($data, 'avatar');
        $email = $this->readString($data, 'email');
        $discriminator = $this->readString($data, 'discriminator');

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['discordId' => $discordId]) ?? new User();

        $user
            ->setDiscordId($discordId)
            ->setDiscordName($displayName)
            ->setUsername($username)
            ->setDiscriminator($discriminator)
            ->setEmail($email ?? $user->getEmail())
            ->setAvatar($avatar ?? $user->getAvatar())
            ->setLastLoginAt(new \DateTimeImmutable());

        $this->syncRolesFromDiscord($user, $data);

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
        if (isset($this->playersByDiscordId[$discordId])) {
            return $this->playersByDiscordId[$discordId];
        }

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

        if (!$isNewPlayer && $this->shouldRefreshPseudoFromDiscord($player, $discordId, $pseudo)) {
            $player->setPseudo($pseudo);
        }

        $player
            ->setDiscordId($discordId)
            ->setAvatar($isNewPlayer ? ($avatar ?? '') : ($avatar ?? $player->getAvatar()))
            ->setSocials($socials);

        $this->playersByDiscordId[$discordId] = $player;

        return $player;
    }

    private function shouldRefreshPseudoFromDiscord(Player $player, string $discordId, string $pseudo): bool
    {
        if ('' === trim($pseudo) || $pseudo === $discordId) {
            return false;
        }

        $currentPseudo = $player->getPseudo();

        return $currentPseudo === $discordId || 1 === preg_match('/^\d{15,22}$/', $currentPseudo);
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

    /**
     * @param array<string, mixed> $data
     */
    private function syncRolesFromDiscord(User $user, array $data): void
    {
        if (!$this->hasDiscordAdminRole($data)) {
            return;
        }

        $roles = $user->getRoles();
        $roles[] = 'ROLE_ADMIN';

        $user->setRoles(array_values(array_unique($roles)));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hasDiscordAdminRole(array $data): bool
    {
        if (true === ($data['isAdmin'] ?? false)) {
            return true;
        }

        $adminRoleId = $this->readEnv('DISCORD_ADMIN_ROLE_ID') ?? self::DefaultDiscordAdminRoleId;
        $roles = $data['roles'] ?? [];

        if (!is_array($roles)) {
            return false;
        }

        foreach ($roles as $role) {
            if (is_array($role) && $adminRoleId === (string) ($role['id'] ?? '')) {
                return true;
            }

            if (!is_array($role) && $adminRoleId === (string) $role) {
                return true;
            }
        }

        return false;
    }

    private function readEnv(string $name): ?string
    {
        $values = [
            $_ENV[$name] ?? null,
            $_SERVER[$name] ?? null,
            getenv($name),
        ];

        foreach ($values as $value) {
            if (!is_scalar($value) && !$value instanceof \Stringable) {
                continue;
            }

            $value = trim((string) $value);

            if ('' !== $value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readRequiredString(array $data, string $key): string
    {
        $value = $this->readString($data, $key);

        if (null === $value) {
            throw new \InvalidArgumentException(sprintf('%s est obligatoire', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (null === $value || '' === $value) {
            return null;
        }

        return (string) $value;
    }
}
