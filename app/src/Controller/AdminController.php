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
use App\Service\PlayerSocialLinks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    private const NewsUploadDirectory = 'uploads/news';
    private const NewsImageMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly PlayerSocialLinks $playerSocialLinks,
    ) {
    }

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
            'matchGames' => GameMatch::Games,
            'matchModes' => GameMatch::Modes,
            'socialNetworks' => $this->playerSocialLinks->allowedNetworks(),
            'socialLabels' => $this->playerSocialLinks->labels(),
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
        $data = $this->decodePayload($request);

        if (null === $data) {
            return $this->json(['message' => 'Donnees invalides'], 400);
        }

        $playedAt = trim((string) ($data['playedAt'] ?? ''));

        if ('' === $playedAt) {
            return $this->json(['message' => 'La date du match est obligatoire'], 400);
        }

        $teamA = $this->findEntity($em, Team::class, $data['teamA'] ?? null);
        $teamB = $this->findEntity($em, Team::class, $data['teamB'] ?? null);
        $roster = $this->findRosterForMatch($em, $data);

        if (!$teamA || !$teamB) {
            return $this->json(['message' => 'Equipe A et equipe B sont obligatoires'], 400);
        }

        if (!$roster) {
            return $this->json(['message' => 'Aucun roster disponible pour rattacher le match'], 400);
        }

        try {
            $game = $this->resolveChoice($data['game'] ?? GameMatch::GameCdl, GameMatch::Games, 'Jeu');
            $mode = $this->resolveChoice($data['mode'] ?? null, GameMatch::Modes, 'Mode');
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
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
            ->setGame($game)
            ->setMode($mode)
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
            return $this->json(['message' => 'Element introuvable ou non modifiable'], 404);
        }

        $entity = $this->findEditableEntity($em, $type, $id);

        if (!$entity) {
            return $this->json(['message' => 'Element introuvable ou non modifiable'], 404);
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
            return $this->json(['message' => 'Element introuvable ou non modifiable'], 404);
        }

        $data = $this->decodePayload($request);

        if (null === $data) {
            return $this->json(['message' => 'Donnees invalides'], 400);
        }

        $entity = $this->findEditableEntity($em, $type, $id);

        if (!$entity) {
            return $this->json(['message' => 'Element introuvable ou non modifiable'], 404);
        }

        if ($entity instanceof News) {
            try {
                $this->updateNews($entity, $data, $request);
            } catch (\RuntimeException $exception) {
                return $this->json(['message' => $exception->getMessage()], 400);
            }
        } elseif ($entity instanceof Player) {
            try {
                $this->updatePlayer($em, $entity, $data);
            } catch (\RuntimeException $exception) {
                return $this->json(['message' => $exception->getMessage()], 400);
            }
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
            try {
                $this->updateMatch($em, $entity, $data);
            } catch (\InvalidArgumentException $exception) {
                return $this->json(['message' => $exception->getMessage()], 400);
            }
            $this->persistMatchStats($em, $entity, $data['stats'] ?? []);
        }

        $em->flush();

        return $this->json(['message' => 'Element modifie avec succes']);
    }

    #[Route('/match/{id}/result', name: 'admin_content_match_result', methods: ['POST'])]
    public function matchResult(GameMatch $match, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $this->decodePayload($request);

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
        $data = $this->decodePayload($request);

        if (null === $data) {
            return $this->json(['message' => 'Donnees invalides'], 400);
        }

        try {
            $this->normalizeFormData($entity, $data, $request);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }

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
    private function normalizeFormData(object $entity, array &$data, Request $request): void
    {
        if ($entity instanceof News) {
            $uploadedImage = $this->uploadNewsImage($request);

            if (null !== $uploadedImage) {
                $data['image'] = $uploadedImage;
            }

            if ('' === trim((string) ($data['image'] ?? ''))) {
                throw new \RuntimeException('Image obligatoire');
            }
        }

        if ($entity instanceof Player) {
            $socials = $this->playerSocialLinks->normalize($data['socials'] ?? []);
            $entity->setSocials($socials);
            $data['socials'] = json_encode($socials, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayload(Request $request): ?array
    {
        if ([] !== $request->request->all()) {
            return $request->request->all();
        }

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
                'socials' => $this->playerSocialLinks->normalize($entity->getSocials(), false),
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
    private function updateNews(News $news, array $data, Request $request): void
    {
        $uploadedImage = $this->uploadNewsImage($request);

        $news
            ->setTitle((string) ($data['title'] ?? $news->getTitle()))
            ->setAuthor((string) ($data['author'] ?? $news->getAuthor()))
            ->setDate(new \DateTimeImmutable((string) ($data['date'] ?? $news->getDate()->format('Y-m-d'))))
            ->setImage((string) ($uploadedImage ?? $data['image'] ?? $news->getImage()))
            ->setExcerpt((string) ($data['excerpt'] ?? $news->getExcerpt()))
            ->setContent((string) ($data['content'] ?? $news->getContent()));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updatePlayer(EntityManagerInterface $em, Player $player, array $data): void
    {
        $hasSocialsPayload = array_key_exists('socials', $data);
        $socials = $this->playerSocialLinks->normalize(
            $hasSocialsPayload ? $data['socials'] : $player->getSocials(),
            $hasSocialsPayload
        );

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

        $event->setRosterEntries([]);
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
            ->setRoster($this->findRosterForMatch($em, $data, $match->getRoster()))
            ->setTournament($this->findEntity($em, Event::class, $data['tournament'] ?? null))
            ->setCaptain($this->findEntity($em, Player::class, $data['captain'] ?? null))
            ->setOpponents($this->normalizeNullableText($data['opponents'] ?? null))
            ->setGame($this->resolveChoice($data['game'] ?? $match->getGame(), GameMatch::Games, 'Jeu'))
            ->setMode($this->resolveChoice($data['mode'] ?? $match->getMode(), GameMatch::Modes, 'Mode'))
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
            'botTournoiId' => $event->getDiscordExternalId() ?? $this->extractBotTournoiId($event),
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

    private function resolveChoice(mixed $value, array $choices, string $label): string
    {
        $value = trim((string) ($value ?? ''));

        if ('' === $value || !in_array($value, $choices, true)) {
            throw new \InvalidArgumentException($label.' invalide');
        }

        return $value;
    }

    private function uploadNewsImage(Request $request): ?string
    {
        $file = $request->files->get('imageFile');

        if (!$file instanceof UploadedFile || UPLOAD_ERR_NO_FILE === $file->getError()) {
            return null;
        }

        if (!$file->isValid()) {
            throw new \RuntimeException('Image invalide');
        }

        $mimeType = $file->getClientMimeType();

        if (!is_string($mimeType) || !array_key_exists($mimeType, self::NewsImageMimeTypes)) {
            throw new \RuntimeException('Format image non autorise');
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', $originalName));
        $safeName = trim($safeName, '-') ?: 'actualite';
        $filename = sprintf(
            '%s-%s.%s',
            $safeName,
            bin2hex(random_bytes(6)),
            self::NewsImageMimeTypes[$mimeType]
        );
        $targetDirectory = $this->projectDir.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.self::NewsUploadDirectory;

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new \RuntimeException('Impossible de creer le dossier upload');
        }

        $file->move($targetDirectory, $filename);

        return '/'.self::NewsUploadDirectory.'/'.$filename;
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
    private function findRosterForMatch(EntityManagerInterface $em, array $data, ?Roster $fallback = null): ?Roster
    {
        $explicitRoster = $this->findEntity($em, Roster::class, $data['roster'] ?? null);

        if ($explicitRoster instanceof Roster) {
            return $explicitRoster;
        }

        if ($fallback instanceof Roster) {
            return $fallback;
        }

        $captain = $this->findEntity($em, Player::class, $data['captain'] ?? null);

        if ($captain instanceof Player && $captain->getRoster() instanceof Roster) {
            return $captain->getRoster();
        }

        foreach (['players', 'substitutes'] as $key) {
            foreach ($this->findPlayers($em, $data[$key] ?? []) as $player) {
                if ($player->getRoster() instanceof Roster) {
                    return $player->getRoster();
                }
            }
        }

        return $em->getRepository(Roster::class)->findOneBy([], ['name' => 'ASC']);
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
