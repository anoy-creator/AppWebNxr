<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class DiscordUserProvider implements OAuthAwareUserProviderInterface, UserProviderInterface
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $data = $response->getData();
        $discordId = $response->getUserIdentifier();
        $username = $data['username'];
        $avatar = $data['avatar'];

        $user = $this->em->getRepository(User::class)->findOneBy(['discordId' => $discordId]);

        if (!$user) {
            $user = new User();
            $user->setDiscordId($discordId);
            $user->setUsername($username);
            $user->setAvatar($avatar);
            $user->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($user);
        } else {
            if ($user->getUsername() !== $username && null != $username) {
                $user->setUsername($username);
            }
            if ($user->getAvatar() !== $avatar) {
                $user->setAvatar($avatar);
            }
        }

        $this->em->flush();

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['discordId' => $identifier]);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User with identifier "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
