<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Service\DiscordAccountLinker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/discord')]
class DiscordController extends AbstractController
{
    #[Route('/register', name: 'api_discord_register', methods: ['POST'])]
    public function register(Request $request, DiscordAccountLinker $discordAccountLinker): JsonResponse
    {
        if (!$this->isValidApiKey($request)) {
            return $this->json([
                'success' => false,
                'message' => 'API key invalide',
            ], 401);
        }

        $data = $this->decodeJsonRequest($request);
        if (null === $data) {
            return $this->json([
                'success' => false,
                'message' => 'JSON invalide',
            ], 400);
        }

        if (empty($data['discordId']) || empty($data['username'])) {
            return $this->json([
                'success' => false,
                'message' => 'discordId et username sont obligatoires',
            ], 400);
        }

        try {
            $user = $discordAccountLinker->syncUserFromDiscord($data);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Compte Discord synchronise',
            'data' => [
                'userId' => $user->getId(),
                'playerId' => $user->getPlayer()?->getId(),
                'discordId' => $user->getDiscordId(),
                'username' => $user->getUsername(),
            ],
        ]);
    }

    #[Route('/ping', name: 'api_discord_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'API operationnelle',
            'timestamp' => time(),
        ]);
    }

    #[Route('/add-tournois', name: 'api_discord_add_tournois', methods: ['POST'])]
    public function addTournois(
        Request $request,
        EntityManagerInterface $entityManager,
        DiscordAccountLinker $discordAccountLinker,
    ): JsonResponse {
        if (!$this->isValidApiKey($request)) {
            return $this->json([
                'success' => false,
                'message' => 'API key invalide',
            ], 401);
        }

        $payload = $this->decodeJsonRequest($request);
        if (null === $payload) {
            return $this->json([
                'success' => false,
                'message' => 'JSON invalide',
            ], 400);
        }

        $tournois = $this->normalizeTournoisPayload($payload);
        $createdEvents = [];

        foreach ($tournois as $tournoiData) {
            if (($tournoiData['status'] ?? 'active') === 'cancelled') {
                continue;
            }

            $event = new Event();
            try {
                $date = $this->buildTournamentDate($tournoiData);
            } catch (\InvalidArgumentException $exception) {
                return $this->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 400);
            }

            $time = (string) ($tournoiData['heure'] ?? $date->format('H:i'));
            $format = $this->normalizeTournamentFormat($tournoiData['format'] ?? null);

            if (null === $format) {
                return $this->json([
                    'success' => false,
                    'message' => sprintf('Format de tournoi invalide. Formats autorises: %s', implode(', ', Event::TournamentFormats)),
                ], 400);
            }

            $event
                ->setType(Event::Tournoi)
                ->setTitle(sprintf('Tournoi %s', $format))
                ->setTournamentFormat($format)
                ->setDescription(sprintf('Tournoi importe depuis Discord. ID bot: %s', $tournoiData['id'] ?? 'inconnu'))
                ->setDate($date)
                ->setTime($time);

            if (!empty($tournoiData['captain'])) {
                $event->setCaptain(
                    $discordAccountLinker->findOrCreatePlayerByDiscordId((string) $tournoiData['captain'])
                );
            }

            foreach ($tournoiData['players'] ?? [] as $discordId) {
                if ($discordId) {
                    $event->addPlayer(
                        $discordAccountLinker->findOrCreatePlayerByDiscordId((string) $discordId)
                    );
                }
            }

            foreach ($tournoiData['substitutes'] ?? [] as $discordId) {
                if ($discordId) {
                    $event->addSubstitute(
                        $discordAccountLinker->findOrCreatePlayerByDiscordId((string) $discordId)
                    );
                }
            }

            $entityManager->persist($event);
            $createdEvents[] = $event;
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Tournois synchronises',
            'created' => array_map(
                static fn (Event $event) => [
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                    'format' => $event->getTournamentFormat(),
                    'date' => $event->getDate()?->format('Y-m-d'),
                    'time' => $event->getTime(),
                ],
                $createdEvents
            ),
        ]);
    }

    #[Route('/add-event', name: 'api_discord_add_event', methods: ['POST'])]
    public function addEvent(
        Request $request,
        EntityManagerInterface $entityManager,
        DiscordAccountLinker $discordAccountLinker,
    ): JsonResponse {
        if (!$this->isValidApiKey($request)) {
            return $this->json([
                'success' => false,
                'message' => 'API key invalide',
            ], 401);
        }

        $data = $this->decodeJsonRequest($request);
        if (null === $data) {
            return $this->json([
                'success' => false,
                'message' => 'JSON invalide',
            ], 400);
        }

        $type = $this->normalizeEventType($data['type'] ?? null);
        if (null === $type) {
            return $this->json([
                'success' => false,
                'message' => sprintf('Type invalide. Types autorises: %s', implode(', ', Event::ScheduleTypes)),
            ], 400);
        }

        try {
            $date = $this->buildTournamentDate($data);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ('' === $title) {
            $title = Event::TypeLabels[$type] ?? 'Event Discord';
        }

        $event = new Event();
        $event
            ->setType($type)
            ->setTitle($title)
            ->setDescription(trim((string) ($data['description'] ?? 'Event importe depuis Discord')))
            ->setDate($date)
            ->setTime((string) ($data['heure'] ?? $data['time'] ?? $date->format('H:i')));

        if (Event::Tournoi === $type) {
            $format = $this->normalizeTournamentFormat($data['format'] ?? $data['tournamentFormat'] ?? null);

            if (null === $format) {
                return $this->json([
                    'success' => false,
                    'message' => sprintf('Format de tournoi invalide. Formats autorises: %s', implode(', ', Event::TournamentFormats)),
                ], 400);
            }

            $event->setTournamentFormat($format);
        }

        if (!empty($data['captain'])) {
            $event->setCaptain(
                $discordAccountLinker->findOrCreatePlayerByDiscordId((string) $data['captain'])
            );
        }

        foreach ($data['players'] ?? [] as $discordId) {
            if ($discordId) {
                $event->addPlayer(
                    $discordAccountLinker->findOrCreatePlayerByDiscordId((string) $discordId)
                );
            }
        }

        foreach ($data['substitutes'] ?? [] as $discordId) {
            if ($discordId) {
                $event->addSubstitute(
                    $discordAccountLinker->findOrCreatePlayerByDiscordId((string) $discordId)
                );
            }
        }

        $entityManager->persist($event);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Event synchronise',
            'event' => [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'type' => $event->getType(),
                'format' => $event->getTournamentFormat(),
                'date' => $event->getDate()?->format('Y-m-d'),
                'time' => $event->getTime(),
            ],
        ]);
    }

    private function isValidApiKey(Request $request): bool
    {
        $expectedApiKey = $_ENV['API_KEY'] ?? null;

        return $expectedApiKey && $request->headers->get('x-api-key') === $expectedApiKey;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonRequest(Request $request): ?array
    {
        $content = $request->getContent();

        if (!$content && $request->request->has('json')) {
            $content = (string) $request->request->get('json');
        }

        $data = json_decode($content, true);

        return JSON_ERROR_NONE === json_last_error() && is_array($data) ? $data : null;
    }

    /**
     * @param array<int|string, mixed> $payload
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTournoisPayload(array $payload): array
    {
        if (array_is_list($payload)) {
            return $payload;
        }

        if (isset($payload['tournois']) && is_array($payload['tournois'])) {
            return $payload['tournois'];
        }

        return [$payload];
    }

    /**
     * @param array<string, mixed> $tournoiData
     */
    private function buildTournamentDate(array $tournoiData): \DateTimeImmutable
    {
        if (!empty($tournoiData['timestamp'])) {
            return (new \DateTimeImmutable())
                ->setTimestamp((int) floor(((int) $tournoiData['timestamp']) / 1000));
        }

        $date = (string) ($tournoiData['date'] ?? 'now');
        $time = (string) ($tournoiData['heure'] ?? $tournoiData['time'] ?? '00:00');

        $format = str_contains($date, '/') ? 'd/m/Y H:i' : 'Y-m-d H:i';
        $dateTime = \DateTimeImmutable::createFromFormat($format, sprintf('%s %s', $date, $time));

        if (!$dateTime) {
            throw new \InvalidArgumentException('Date de tournoi invalide');
        }

        return $dateTime;
    }

    private function normalizeTournamentFormat(mixed $format): ?string
    {
        $format = strtolower(trim((string) ($format ?? '')));

        return in_array($format, Event::TournamentFormats, true) ? $format : null;
    }

    private function normalizeEventType(mixed $type): ?string
    {
        $type = strtolower(trim((string) ($type ?? '')));

        return in_array($type, Event::ScheduleTypes, true) ? $type : null;
    }
}
