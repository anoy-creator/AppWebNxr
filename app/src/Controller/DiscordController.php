<?php

namespace App\Controller;

use App\Service\DiscordAccountLinker;
use App\Service\DiscordGuildRoleResolver;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class DiscordController extends AbstractController
{
    #[Route('/connect/discord', name: 'connect_discord_start')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('discord')
            ->redirect(['identify', 'email', 'guilds.members.read']);
    }

    #[Route('/auth/discord/callback', name: 'connect_discord_check')]
    public function connectCheck(
        ClientRegistry $clientRegistry,
        DiscordAccountLinker $discordAccountLinker,
        DiscordGuildRoleResolver $discordGuildRoleResolver,
        Security $security,
    ): RedirectResponse {
        try {
            $client = $clientRegistry->getClient('discord');
            $accessToken = $client->getAccessToken();
            $discordUser = $client->fetchUserFromToken($accessToken);
        } catch (IdentityProviderException|\RuntimeException|\LogicException $exception) {
            $this->addFlash('error', 'Connexion Discord echouee.');

            return $this->redirectToRoute('app_login');
        }

        $discordData = $discordUser->toArray();
        $discordId = (string) ($discordData['id'] ?? '');

        if ('' === $discordId) {
            $this->addFlash('error', 'Discord n a pas renvoye d identifiant utilisateur.');

            return $this->redirectToRoute('app_login');
        }

        $user = $discordAccountLinker->syncUserFromDiscord([
            'discordId' => $discordId,
            'username' => $discordData['username'] ?? $discordId,
            'displayName' => $discordData['global_name'] ?? $discordData['username'] ?? $discordId,
            'discriminator' => $discordData['discriminator'] ?? null,
            'email' => $discordData['email'] ?? null,
            'avatar' => $this->buildDiscordAvatarUrl($discordId, $discordData['avatar'] ?? null),
            'isAdmin' => $discordGuildRoleResolver->memberHasRoleFromUserAccessToken($accessToken->getToken())
                || $discordGuildRoleResolver->memberHasRole($discordId),
        ]);

        $this->addFlash('success', 'Compte Discord connecte.');

        return $security->login($user, 'form_login', 'main')
            ?? $this->redirectToRoute('app_index');
    }

    private function buildDiscordAvatarUrl(string $discordId, ?string $avatarHash): ?string
    {
        if (!$avatarHash) {
            return null;
        }

        if (str_starts_with($avatarHash, 'http://') || str_starts_with($avatarHash, 'https://')) {
            return $avatarHash;
        }

        $extension = str_starts_with($avatarHash, 'a_') ? 'gif' : 'png';

        return sprintf(
            'https://cdn.discordapp.com/avatars/%s/%s.%s?size=256',
            $discordId,
            $avatarHash,
            $extension,
        );
    }
}
