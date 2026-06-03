<?php

namespace App\Security;

use App\Entity\User;
use App\Service\DiscordAccountLinker;
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
    private DiscordAccountLinker $discordAccountLinker;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        DiscordAccountLinker $discordAccountLinker,
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->discordAccountLinker = $discordAccountLinker;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $data = $response->getData();
        $discordId = $response->getUserIdentifier();
        $username = $data['username'] ?? $discordId;
        $avatar = $data['avatar'] ?? null;

        return $this->discordAccountLinker->syncUserFromDiscord([
            'discordId' => $discordId,
            'username' => $username,
            'displayName' => $data['global_name'] ?? $data['displayName'] ?? $username,
            'avatar' => $avatar,
        ]);
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
