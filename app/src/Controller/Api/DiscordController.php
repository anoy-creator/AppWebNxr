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
        $syncedEvents = [];

        foreach ($tournois as $tournoiData) {
            if (($tournoiData['status'] ?? 'active') === 'cancelled') {
                continue;
            }

            try {
                $event = $this->findTournamentEvent($entityManager, $tournoiData) ?? new Event();
                $this->syncTournamentEvent($event, $tournoiData, $discordAccountLinker);
            } catch (\InvalidArgumentException $exception) {
                return $this->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 400);
            }

            $entityManager->persist($event);
            $syncedEvents[] = $event;
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
                    'checkins' => $event->getCheckins(),
                ],
                $syncedEvents
            ),
        ]);
    }

    #[Route('/tournoi-checkin', name: 'api_discord_tournoi_checkin', methods: ['POST'])]
    public function tournoiCheckin(Request $request, EntityManagerInterface $entityManager): JsonResponse
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

        $discordId = trim((string) ($data['discordId'] ?? ''));
        $status = trim((string) ($data['status'] ?? ''));

        if ('' === $discordId || !in_array($status, ['available', 'unavailable'], true)) {
            return $this->json([
                'success' => false,
                'message' => 'discordId et status valide sont obligatoires',
            ], 400);
        }

        $event = $this->findTournamentEvent($entityManager, $data);

        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Tournoi site introuvable',
            ], 404);
        }

        $event->setCheckin($discordId, $status);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Presence tournoi synchronisee',
            'event' => [
                'id' => $event->getId(),
                'checkins' => $event->getCheckins(),
            ],
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
    private function findTournamentEvent(EntityManagerInterface $entityManager, array $tournoiData): ?Event
    {
        $siteEventId = $tournoiData['siteEventId'] ?? $tournoiData['eventId'] ?? null;

        if ($siteEventId) {
            $event = $entityManager->getRepository(Event::class)->find((int) $siteEventId);

            if ($event instanceof Event) {
                return $event;
            }
        }

        $botTournoiId = trim((string) ($tournoiData['botTournoiId'] ?? $tournoiData['id'] ?? ''));

        if ('' === $botTournoiId) {
            return null;
        }

        $event = $entityManager->getRepository(Event::class)
            ->findOneBy(['discordExternalId' => $botTournoiId]);

        if ($event instanceof Event) {
            return $event;
        }

        return $entityManager->getRepository(Event::class)
            ->createQueryBuilder('event')
            ->andWhere('event.type = :type')
            ->andWhere('event.description LIKE :botId')
            ->setParameter('type', Event::Tournoi)
            ->setParameter('botId', sprintf('%%ID bot: %s%%', $botTournoiId))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array<string, mixed> $tournoiData
     */
    private function syncTournamentEvent(
        Event $event,
        array $tournoiData,
        DiscordAccountLinker $discordAccountLinker,
    ): void {
        $date = $this->buildTournamentDate($tournoiData);
        $time = (string) ($tournoiData['heure'] ?? $date->format('H:i'));
        $format = $this->normalizeTournamentFormat($tournoiData['format'] ?? null);

        if (null === $format) {
            throw new \InvalidArgumentException(sprintf('Format de tournoi invalide. Formats autorises: %s', implode(', ', Event::TournamentFormats)));
        }

        $botTournoiId = trim((string) ($tournoiData['botTournoiId'] ?? $tournoiData['id'] ?? 'inconnu'));
        $title = trim((string) ($tournoiData['title'] ?? ''));

        $event
            ->setType(Event::Tournoi)
            ->setTitle('' !== $title ? $title : sprintf('Tournoi %s', $format))
            ->setTournamentFormat($format)
            ->setDiscordExternalId($botTournoiId)
            ->setDescription('Tournoi importe depuis Discord.')
            ->setDate($date)
            ->setTime($time);

        $captainDiscordId = trim((string) ($tournoiData['captain'] ?? ''));
        $event->setCaptain(null);

        if ('' !== $captainDiscordId) {
            $event->setCaptain($this->findOrCreateTournamentPlayer($discordAccountLinker, $captainDiscordId, $tournoiData));
        }

        $players = array_values(array_filter(
            $this->normalizeDiscordIds($tournoiData['players'] ?? [], true),
            fn (string $discordId) => $discordId !== $captainDiscordId,
        ));
        $substitutes = array_values(array_filter(
            $this->normalizeDiscordIds($tournoiData['substitutes'] ?? [], true),
            fn (string $discordId) => $discordId !== $captainDiscordId && !in_array($discordId, $players, true),
        ));

        foreach ($event->getPlayers()->toArray() as $player) {
            $event->removePlayer($player);
        }

        foreach ($event->getSubstitutes()->toArray() as $substitute) {
            $event->removeSubstitute($substitute);
        }

        foreach ($players as $discordId) {
            $event->addPlayer($this->findOrCreateTournamentPlayer($discordAccountLinker, $discordId, $tournoiData));
        }

        foreach ($substitutes as $discordId) {
            $event->addSubstitute($this->findOrCreateTournamentPlayer($discordAccountLinker, $discordId, $tournoiData));
        }

        if (isset($tournoiData['checkins']) && is_array($tournoiData['checkins'])) {
            $event->setCheckins($this->normalizeCheckins($tournoiData['checkins']));
        }

        $event->setRosterEntries($this->buildTournamentRosterEntries($discordAccountLinker, $tournoiData, $captainDiscordId));
    }

    /**
     * @param array<string, mixed> $tournoiData
     */
    private function findOrCreateTournamentPlayer(
        DiscordAccountLinker $discordAccountLinker,
        string $discordId,
        array $tournoiData,
    ): \App\Entity\Player {
        $profile = $this->findDiscordMemberProfile($tournoiData, $discordId);

        return $discordAccountLinker->findOrCreatePlayerByDiscordId(
            $discordId,
            $profile['displayName'] ?? $profile['username'] ?? $discordId,
            $profile['avatar'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $tournoiData
     *
     * @return array<string, string>
     */
    private function findDiscordMemberProfile(array $tournoiData, string $discordId): array
    {
        $members = $tournoiData['members'] ?? [];

        if (is_array($members) && isset($members[$discordId]) && is_array($members[$discordId])) {
            return array_filter([
                'username' => isset($members[$discordId]['username']) ? (string) $members[$discordId]['username'] : null,
                'displayName' => isset($members[$discordId]['displayName']) ? (string) $members[$discordId]['displayName'] : null,
                'avatar' => isset($members[$discordId]['avatar']) ? (string) $members[$discordId]['avatar'] : null,
            ], static fn (?string $value) => null !== $value && '' !== $value);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $tournoiData
     *
     * @return list<array<string, string>>
     */
    private function buildTournamentRosterEntries(
        DiscordAccountLinker $discordAccountLinker,
        array $tournoiData,
        string $captainDiscordId,
    ): array {
        $entries = [];

        if ('' !== $captainDiscordId) {
            $entries[] = $this->buildTournamentRosterEntry(
                $discordAccountLinker,
                $tournoiData,
                $captainDiscordId,
                'Capitaine',
            );
        }

        foreach ($this->normalizeDiscordIds($tournoiData['players'] ?? [], false) as $discordId) {
            $entries[] = $this->buildTournamentRosterEntry(
                $discordAccountLinker,
                $tournoiData,
                $discordId,
                'Titulaire',
            );
        }

        foreach ($this->normalizeDiscordIds($tournoiData['substitutes'] ?? [], false) as $discordId) {
            $entries[] = $this->buildTournamentRosterEntry(
                $discordAccountLinker,
                $tournoiData,
                $discordId,
                'Remplacant',
            );
        }

        return $entries;
    }

    /**
     * @param array<string, mixed> $tournoiData
     *
     * @return array<string, string>
     */
    private function buildTournamentRosterEntry(
        DiscordAccountLinker $discordAccountLinker,
        array $tournoiData,
        string $discordId,
        string $role,
    ): array {
        $player = $this->findOrCreateTournamentPlayer($discordAccountLinker, $discordId, $tournoiData);

        return [
            'discordId' => $discordId,
            'pseudo' => $player->getPseudo(),
            'grade' => $player->getGrade(),
            'role' => $role,
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeDiscordIds(mixed $ids, bool $unique = true): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $normalized = [];

        foreach ($ids as $id) {
            $id = trim((string) $id);

            if ('' !== $id) {
                $normalized[] = $id;
            }
        }

        return $unique ? array_values(array_unique($normalized)) : $normalized;
    }

    /**
     * @param array<string, mixed> $checkins
     *
     * @return array<string, string>
     */
    private function normalizeCheckins(array $checkins): array
    {
        $normalized = [];

        foreach ($checkins as $discordId => $status) {
            $discordId = trim((string) $discordId);
            $status = trim((string) $status);

            if ('' !== $discordId && in_array($status, ['available', 'unavailable'], true)) {
                $normalized[$discordId] = $status;
            }
        }

        return $normalized;
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
