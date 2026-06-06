<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
class DiscordUserProvider implements UserProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['discordId' => $identifier])
            ?? $this->entityManager->getRepository(User::class)->findOneBy(['username' => $identifier]);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User with identifier "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
