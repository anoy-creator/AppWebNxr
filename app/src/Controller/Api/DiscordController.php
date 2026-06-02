<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/discord')]
class DiscordController extends AbstractController
{
    #[Route('/register', name: 'api_discord_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $receivedApiKey = $request->headers->get('x-api-key');

        if ($receivedApiKey !== $_ENV['API_KEY']) {
            return $this->json([
                'success' => false,
                'message' => 'API key invalide',
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'success' => false,
                'message' => 'JSON invalide',
            ], 400);
        }

        if (
            empty($data['discordId']) ||
            empty($data['username'])
        ) {
            return $this->json([
                'success' => false,
                'message' => 'discordId et username sont obligatoires',
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Données Discord reçues',
            'data' => [
                'discordId' => $data['discordId'] ?? null,
                'username' => $data['username'] ?? null,
                'displayName' => $data['displayName'] ?? null,
                'avatar' => $data['avatar'] ?? null,
                'guildId' => $data['guildId'] ?? null,
                'guildName' => $data['guildName'] ?? null,
                'roles' => $data['roles'] ?? [],
            ],
        ]);
    }

    #[Route('/ping', name: 'api_discord_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'API opérationnelle',
            'timestamp' => time(),
        ]);
    }
}
