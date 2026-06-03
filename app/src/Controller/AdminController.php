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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/content')]
class AdminController extends AbstractController
{
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

        if ($data === null) {
            return $this->json(['message' => 'Donnees invalides'], 400);
        }

        $playedAt = trim((string) ($data['playedAt'] ?? ''));

        if ($playedAt === '') {
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

    #[Route('/match/{id}/result', name: 'admin_content_match_result', methods: ['POST'])]
    public function matchResult(GameMatch $match, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
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
        if ($event->getType() !== 'tournament') {
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

        if ($data === null) {
            return $this->json(['message' => 'Donnees invalides'], 400);
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

    private function decodeJsonPayload(Request $request): ?array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : null;
    }

    private function createPlayedAt(string $date, mixed $time): \DateTimeImmutable
    {
        $time = trim((string) ($time ?? '00:00'));

        return new \DateTimeImmutable($date . ' ' . ($time ?: '00:00'));
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function findEntity(EntityManagerInterface $em, string $class, mixed $id): ?object
    {
        if ($id === null || $id === '') {
            return null;
        }

        return $em->getRepository($class)->find((int) $id);
    }

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
            $ids = $ids === null || $ids === '' ? [] : [$ids];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        return $em->getRepository(Player::class)->findBy(['id' => $ids]);
    }
}
