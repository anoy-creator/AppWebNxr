<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\GameMatch;
use App\Entity\News;
use App\Entity\Player;
use App\Entity\PlayerMatchStat;
use App\Entity\Roster;
use App\Entity\Team;
use App\Form\EventType;
use App\Form\NewsType;
use App\Form\PlayerType;
use App\Form\RosterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/content')]
class AdminController extends AbstractController
{
    private const AllowedModals = ['add-news', 'add-player', 'add-roster', 'add-event', 'add-match'];
    private const EditableTypes = ['news', 'player', 'roster', 'event', 'match'];

    #[Route('/modal/{modal}', name: 'admin_content_modal', methods: ['GET'])]
    public function modal(string $modal, EntityManagerInterface $em): Response
    {
        if (!in_array($modal, self::AllowedModals, true)) {
            throw $this->createNotFoundException('Modale introuvable');
        }

        return $this->render('pages/admin/modals.html.twig', [
            'modal' => $modal,
            'players' => $em->getRepository(Player::class)->findBy([], ['pseudo' => 'ASC']),
            'teams' => $em->getRepository(Team::class)->findBy([], ['name' => 'ASC']),
            'rosters' => $em->getRepository(Roster::class)->findBy([], ['name' => 'ASC']),
            'tournaments' => $em->getRepository(Event::class)->findBy([
                'type' => Event::Tournoi,
            ], [
                'date' => 'DESC',
            ]),
        ]);
    }

    #[Route('/news', name: 'admin_content_news', methods: ['POST'])]
    public function news(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleForm($request, $em, new News(), NewsType::class);
    }

    #[Route('/player', name: 'admin_content_player', methods: ['POST'])]
    public function player(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleForm($request, $em, new Player(), PlayerType::class);
    }

    #[Route('/roster', name: 'admin_content_roster', methods: ['POST'])]
    public function roster(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleForm($request, $em, new Roster(), RosterType::class);
    }

    #[Route('/event', name: 'admin_content_event', methods: ['POST'])]
    public function event(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleForm($request, $em, new Event(), EventType::class);
    }

    #[Route('/match', name: 'admin_content_match', methods: ['POST'])]
    public function match(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $this->decodeJsonPayload($request);

        if (null === $data) {
            return $this->json(['message' => 'Donnees invalides'], 400);
        }

        $playedAt = trim((string) ($data['playedAt'] ?? ''));

        if ('' === $playedAt) {
            return $this->json(['message' => 'La date du match est obligatoire'], 400);
        }

        $teamA = $this->findEntity($em, Team::class, $data['teamA'] ?? null);
        $teamB = $this->findEntity($em, Team::class, $data['teamB'] ?? null);
        $roster = $this->findEntity($em, Roster::class, $data['roster'] ?? null);

        if (!$teamA || !$teamB || !$roster) {
            return $this->json(['message' => 'Equipe A, equipe B et roster sont obligatoires'], 400);
        }

        $match = new GameMatch();
        $match
            ->setPlayedAt($this->createPlayedAt($playedAt, $data['playedTime'] ?? null))
            ->setTeamA($teamA)
            ->setTeamB($teamB)
            ->setRoster($roster)
            ->setTournament($this->findEntity($em, Event::class, $data['tournament'] ?? null))
            ->setCaptain($this->findEntity($em, Player::class, $data['captain'] ?? null))
            ->setOpponents($this->normalizeNullableText($data['opponents'] ?? null))
            ->setGame((string) ($data['game'] ?? 'CDL'))
            ->setMode((string) ($data['mode'] ?? ''))
            ->setMapName($this->normalizeNullableText($data['mapName'] ?? null))
            ->setResult($this->normalizeNullableText($data['result'] ?? null))
            ->setScore($this->normalizeNullableText($data['score'] ?? null));

        $this->syncMatchPlayers($em, $match, $data);

        $em->persist($match);
        $this->persistMatchStats($em, $match, $data['stats'] ?? []);
        $em->flush();

        return $this->json(['message' => 'Match ajoute avec succes']);
    }

    #[Route('/edit/{type}/{id}', name: 'admin_content_edit_data', methods: ['GET'])]
    public function editData(string $type, int $id, EntityManagerInterface $em): JsonResponse
    {
        if (!in_array($type, self::EditableTypes, true)) {
            throw $this->createNotFoundException('Type introuvable');
        }

        $entity = $this->findEditableEntity($em, $type, $id);

        if (!$entity) {
            throw $this->createNotFoundException('Element introuvable');
        }

        $values = $this->normalizeEditableEntity($entity);

        if ($entity instanceof GameMatch) {
            $values['stats'] = array_map(
                static fn (PlayerMatchStat $stat) => [
                    'player' => $stat->getPlayer()->getId(),
                    'kills' => $stat->getKills(),
                    'deaths' => $stat->getDeaths(),
                ],
                $em->getRepository(PlayerMatchStat::class)->findBy(['match' => $entity])
            );
        }

        return $this->json([
            'type' => $type,
            'id' => $id,
            'modal' => 'add-'.$type,
            'endpoint' => $this->generateUrl('admin_content_edit_save', [
                'type' => $type,
                'id' => $id,
            ]),
            'values' => $values,
        ]);
    }

    #[Route('/edit/{type}/{id}', name: 'admin_content_edit_save', methods: ['PATCH', 'POST'])]
    public function editSave(string $type, int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!in_array($type, self::EditableTypes, true)) {
            throw $this->createNotFoundException('Type introuvable');
        }

        $data = $this->decodeJsonPayload($request);

        if (null === $data) {
            return $this->json(['message' => 'Donnees invalides'], 400);
        }

        $entity = $this->findEditableEntity($em, $type, $id);

        if (!$entity) {
            throw $this->createNotFoundException('Element introuvable');
        }

        if ($entity instanceof News) {
            $this->updateNews($entity, $data);
        } elseif ($entity instanceof Player) {
            $this->updatePlayer($em, $entity, $data);
        } elseif ($entity instanceof Roster) {
            $this->updateRoster($entity, $data);
        } elseif ($entity instanceof Event) {
            $wasTournament = Event::Tournoi === $entity->getType();
            $this->updateEvent($em, $entity, $data);
            $em->flush();

            if ($wasTournament || Event::Tournoi === $entity->getType()) {
                $this->notifyBotTournamentUpdated($entity);
            }

            return $this->json(['message' => 'Event modifie avec succes']);
        } elseif ($entity instanceof GameMatch) {
            $this->updateMatch($em, $entity, $data);
            $this->persistMatchStats($em, $entity, $data['stats'] ?? []);
        }

        $em->flush();

        return $this->json(['message' => 'Element modifie avec succes']);
    }

    #[Route('/match/{id}/result', name: 'admin_content_match_result', methods: ['POST'])]
    public function matchResult(GameMatch $match, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $this->decodeJsonPayload($request);

        if (null === $data) {
            return $this->json(['message' => 'Donnees invalides'], 400);
        }

        $match
            ->setResult($this->normalizeNullableText($data['result'] ?? null))
            ->setScore($this->normalizeNullableText($data['score'] ?? null));

        $this->persistMatchStats($em, $match, $data['stats'] ?? []);
        $em->flush();

        return $this->json(['message' => 'Resultat du match enregistre']);
    }

    #[Route('/tournament/{id}/players', name: 'admin_content_tournament_players', methods: ['GET'])]
    public function tournamentPlayers(Event $event): JsonResponse
    {
        if ('tournament' !== $event->getType()) {
            return $this->json([
                'message' => 'Cet event n est pas un tournoi',
            ], 400);
        }

        return $this->json([
            'captain' => $event->getCaptain()?->getId(),
            'players' => array_map(
                static fn (Player $player) => $player->getId(),
                $event->getPlayers()->toArray()
            ),
            'substitutes' => array_map(
                static fn (Player $player) => $player->getId(),
                $event->getSubstitutes()->toArray()
            ),
        ]);
    }

    private function handleForm(Request $request, EntityManagerInterface $em, object $entity, string $formClass): JsonResponse
    {
        $data = $this->decodeJsonPayload($request);

        if (null === $data) {
            return $this->json(['message' => 'Donnees invalides'], 400);
        }

        $this->normalizeFormData($entity, $data);

        $form = $this->createForm($formClass, $entity);
        $form->submit($data);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = [];

            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return $this->json([
                'message' => $errors[0] ?? 'Formulaire invalide',
                'errors' => $errors,
            ], 400);
        }

        $em->persist($entity);
        $em->flush();

        return $this->json(['message' => 'Element ajoute avec succes']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function normalizeFormData(object $entity, array &$data): void
    {
        if ($entity instanceof Player && isset($data['socials']) && is_string($data['socials'])) {
            $decodedSocials = json_decode($data['socials'], true);
            $data['socials'] = is_array($decodedSocials) ? $decodedSocials : [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonPayload(Request $request): ?array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : null;
    }

    private function createPlayedAt(string $date, mixed $time): \DateTimeImmutable
    {
        $time = trim((string) ($time ?? '00:00'));

        return new \DateTimeImmutable($date.' '.($time ?: '00:00'));
    }

    private function findEditableEntity(EntityManagerInterface $em, string $type, int $id): ?object
    {
        return match ($type) {
            'news' => $em->getRepository(News::class)->find($id),
            'player' => $em->getRepository(Player::class)->find($id),
            'roster' => $em->getRepository(Roster::class)->find($id),
            'event' => $em->getRepository(Event::class)->find($id),
            'match' => $em->getRepository(GameMatch::class)->find($id),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeEditableEntity(object $entity): array
    {
        if ($entity instanceof News) {
            return [
                'title' => $entity->getTitle(),
                'author' => $entity->getAuthor(),
                'date' => $entity->getDate()->format('Y-m-d'),
                'image' => $entity->getImage(),
                'excerpt' => $entity->getExcerpt(),
                'content' => $entity->getContent(),
            ];
        }

        if ($entity instanceof Player) {
            return [
                'pseudo' => $entity->getPseudo(),
                'discordId' => $entity->getDiscordId(),
                'avatar' => $entity->getAvatar(),
                'role' => $entity->getRole(),
                'grade' => $entity->getGrade(),
                'game' => $entity->getGame(),
                'roster' => $entity->getRoster()?->getId(),
                'socials' => json_encode($entity->getSocials(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
        }

        if ($entity instanceof Roster) {
            return [
                'name' => $entity->getName(),
                'game' => $entity->getGame(),
                'banner' => $entity->getBanner(),
                'wins' => $entity->getWins(),
                'losses' => $entity->getLosses(),
                'winrate' => $entity->getWinrate(),
            ];
        }

        if ($entity instanceof Event) {
            return [
                'title' => $entity->getTitle(),
                'type' => $entity->getType(),
                'tournamentFormat' => $entity->getTournamentFormat(),
                'date' => $entity->getDate()?->format('Y-m-d'),
                'time' => $entity->getTime(),
                'description' => $entity->getDescription(),
                'captain' => $entity->getCaptain()?->getId(),
                'players' => array_map(static fn (Player $player) => $player->getId(), $entity->getPlayers()->toArray()),
                'substitutes' => array_map(static fn (Player $player) => $player->getId(), $entity->getSubstitutes()->toArray()),
            ];
        }

        if ($entity instanceof GameMatch) {
            return [
                'playedAt' => $entity->getPlayedAt()?->format('Y-m-d'),
                'playedTime' => $entity->getPlayedAt()?->format('H:i'),
                'teamA' => $entity->getTeamA()?->getId(),
                'teamB' => $entity->getTeamB()?->getId(),
                'roster' => $entity->getRoster()?->getId(),
                'tournament' => $entity->getTournament()?->getId(),
                'captain' => $entity->getCaptain()?->getId(),
                'players' => array_map(static fn (Player $player) => $player->getId(), $entity->getPlayers()->toArray()),
                'substitutes' => array_map(static fn (Player $player) => $player->getId(), $entity->getSubstitutes()->toArray()),
                'opponents' => $entity->getOpponents(),
                'game' => $entity->getGame(),
                'mode' => $entity->getMode(),
                'mapName' => $entity->getMapName(),
                'result' => $entity->getResult(),
                'score' => $entity->getScore(),
            ];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateNews(News $news, array $data): void
    {
        $news
            ->setTitle((string) ($data['title'] ?? $news->getTitle()))
            ->setAuthor((string) ($data['author'] ?? $news->getAuthor()))
            ->setDate(new \DateTimeImmutable((string) ($data['date'] ?? $news->getDate()->format('Y-m-d'))))
            ->setImage((string) ($data['image'] ?? $news->getImage()))
            ->setExcerpt((string) ($data['excerpt'] ?? $news->getExcerpt()))
            ->setContent((string) ($data['content'] ?? $news->getContent()));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updatePlayer(EntityManagerInterface $em, Player $player, array $data): void
    {
        $socials = $data['socials'] ?? $player->getSocials();

        if (is_string($socials)) {
            $decodedSocials = json_decode($socials, true);
            $socials = is_array($decodedSocials) ? $decodedSocials : [];
        }

        $player
            ->setPseudo((string) ($data['pseudo'] ?? $player->getPseudo()))
            ->setDiscordId($this->normalizeNullableText($data['discordId'] ?? $player->getDiscordId()))
            ->setAvatar((string) ($data['avatar'] ?? $player->getAvatar()))
            ->setRole((string) ($data['role'] ?? $player->getRole()))
            ->setGrade((string) ($data['grade'] ?? $player->getGrade()))
            ->setGame((string) ($data['game'] ?? $player->getGame()))
            ->setRoster($this->findEntity($em, Roster::class, $data['roster'] ?? null))
            ->setSocials($socials);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateRoster(Roster $roster, array $data): void
    {
        $roster
            ->setName((string) ($data['name'] ?? $roster->getName()))
            ->setGame((string) ($data['game'] ?? $roster->getGame()))
            ->setBanner((string) ($data['banner'] ?? $roster->getBanner()))
            ->setWins((int) ($data['wins'] ?? $roster->getWins()))
            ->setLosses((int) ($data['losses'] ?? $roster->getLosses()))
            ->setWinrate((string) ($data['winrate'] ?? $roster->getWinrate()));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateEvent(EntityManagerInterface $em, Event $event, array $data): void
    {
        $event
            ->setTitle((string) ($data['title'] ?? $event->getTitle()))
            ->setType((string) ($data['type'] ?? $event->getType()))
            ->setTournamentFormat($this->normalizeNullableText($data['tournamentFormat'] ?? null))
            ->setDate(new \DateTimeImmutable((string) ($data['date'] ?? $event->getDate()?->format('Y-m-d') ?? 'now')))
            ->setTime((string) ($data['time'] ?? $event->getTime() ?? '00:00'))
            ->setDescription((string) ($data['description'] ?? $event->getDescription()))
            ->setCaptain($this->findEntity($em, Player::class, $data['captain'] ?? null));

        foreach ($event->getPlayers()->toArray() as $player) {
            $event->removePlayer($player);
        }

        foreach ($event->getSubstitutes()->toArray() as $player) {
            $event->removeSubstitute($player);
        }

        foreach ($this->findPlayers($em, $data['players'] ?? []) as $player) {
            $event->addPlayer($player);
        }

        foreach ($this->findPlayers($em, $data['substitutes'] ?? []) as $player) {
            $event->addSubstitute($player);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateMatch(EntityManagerInterface $em, GameMatch $match, array $data): void
    {
        $match
            ->setPlayedAt($this->createPlayedAt((string) ($data['playedAt'] ?? $match->getPlayedAt()?->format('Y-m-d')), $data['playedTime'] ?? $match->getPlayedAt()?->format('H:i')))
            ->setTeamA($this->findEntity($em, Team::class, $data['teamA'] ?? null))
            ->setTeamB($this->findEntity($em, Team::class, $data['teamB'] ?? null))
            ->setRoster($this->findEntity($em, Roster::class, $data['roster'] ?? null))
            ->setTournament($this->findEntity($em, Event::class, $data['tournament'] ?? null))
            ->setCaptain($this->findEntity($em, Player::class, $data['captain'] ?? null))
            ->setOpponents($this->normalizeNullableText($data['opponents'] ?? null))
            ->setGame((string) ($data['game'] ?? $match->getGame()))
            ->setMode((string) ($data['mode'] ?? $match->getMode()))
            ->setMapName($this->normalizeNullableText($data['mapName'] ?? null))
            ->setResult($this->normalizeNullableText($data['result'] ?? null))
            ->setScore($this->normalizeNullableText($data['score'] ?? null));

        foreach ($match->getPlayers()->toArray() as $player) {
            $match->removePlayer($player);
        }

        foreach ($match->getSubstitutes()->toArray() as $player) {
            $match->removeSubstitute($player);
        }

        $this->syncMatchPlayers($em, $match, $data);
    }

    private function notifyBotTournamentUpdated(Event $event): void
    {
        $webhookUrl = $_ENV['BOT_WEBHOOK_URL'] ?? $_ENV['DISCORD_BOT_WEBHOOK_URL'] ?? 'http://discord_bot:3010/site/tournament-updated';
        $apiKey = $_ENV['API_KEY'] ?? null;

        if (!$apiKey || Event::Tournoi !== $event->getType()) {
            return;
        }

        $payload = [
            'siteEventId' => $event->getId(),
            'botTournoiId' => $this->extractBotTournoiId($event),
            'title' => $event->getTitle(),
            'date' => $event->getDate()?->format('d/m/Y'),
            'heure' => $event->getTime(),
            'format' => $event->getTournamentFormat(),
            'timestamp' => $event->getDate() ? (new \DateTimeImmutable(sprintf('%s %s', $event->getDate()->format('Y-m-d'), $event->getTime() ?: '00:00')))->getTimestamp() * 1000 : null,
            'captain' => $event->getCaptain()?->getDiscordId(),
            'players' => array_values(array_filter(array_map(static fn (Player $player) => $player->getDiscordId(), $event->getPlayers()->toArray()))),
            'substitutes' => array_values(array_filter(array_map(static fn (Player $player) => $player->getDiscordId(), $event->getSubstitutes()->toArray()))),
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'x-api-key: '.$apiKey,
                ],
                'content' => json_encode($payload, JSON_THROW_ON_ERROR),
                'timeout' => 2,
                'ignore_errors' => true,
            ],
        ]);

        @file_get_contents($webhookUrl, false, $context);
    }

    private function extractBotTournoiId(Event $event): ?string
    {
        if (preg_match('/ID bot:\s*([^\s]+)/', $event->getDescription() ?? '', $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return '' === $value ? null : $value;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    private function findEntity(EntityManagerInterface $em, string $class, mixed $id): ?object
    {
        if (null === $id || '' === $id) {
            return null;
        }

        return $em->getRepository($class)->find((int) $id);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function syncMatchPlayers(EntityManagerInterface $em, GameMatch $match, array $data): void
    {
        $matchPlayerIds = [];

        foreach ($this->findPlayers($em, $data['players'] ?? []) as $player) {
            $match->addPlayer($player);
            $matchPlayerIds[] = $player->getId();
        }

        foreach ($this->findPlayers($em, $data['substitutes'] ?? []) as $player) {
            if (in_array($player->getId(), $matchPlayerIds, true)) {
                continue;
            }

            $match->addSubstitute($player);
        }
    }

    private function persistMatchStats(EntityManagerInterface $em, GameMatch $match, mixed $rows): void
    {
        if (!is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['player'])) {
                continue;
            }

            $player = $this->findEntity($em, Player::class, $row['player']);

            if (!$player instanceof Player) {
                continue;
            }

            $stat = $em->getRepository(PlayerMatchStat::class)->findOneBy([
                'match' => $match,
                'player' => $player,
            ]) ?? new PlayerMatchStat();

            $stat
                ->setMatch($match)
                ->setPlayer($player)
                ->setKills(max(0, (int) ($row['kills'] ?? 0)))
                ->setDeaths(max(0, (int) ($row['deaths'] ?? 0)));

            $em->persist($stat);
        }
    }

    /**
     * @return Player[]
     */
    private function findPlayers(EntityManagerInterface $em, mixed $ids): array
    {
        if (!is_array($ids)) {
            $ids = null === $ids || '' === $ids ? [] : [$ids];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ([] === $ids) {
            return [];
        }

        return $em->getRepository(Player::class)->findBy(['id' => $ids]);
    }
}
