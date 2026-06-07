<?php

namespace App\Controller;

use App\Entity\GameMatch;
use App\Repository\GameMatchRepository;
use App\Repository\PlayerMatchStatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MatchesController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/matches', name: 'app_matches')]
    public function index(
        Request $request,
        GameMatchRepository $gameMatchRepository,
    ): Response {
        $game = (string) $request->query->get('game', 'Tous');
        $matchFilters = array_merge(['Tous'], GameMatch::Games);

        if (!in_array($game, $matchFilters, true)) {
            $game = 'Tous';
        }

        $criteria = [];
        $tournamentGroups = [];

        if (GameMatch::GameTournament === $game) {
            $games = $gameMatchRepository->createQueryBuilder('m')
                ->leftJoin('m.tournament', 't')
                ->addSelect('t')
                ->andWhere('m.tournament IS NOT NULL')
                ->orderBy('m.playedAt', 'DESC')
                ->getQuery()
                ->getResult();

            foreach ($games as $match) {
                $tournament = $match->getTournament();

                if (null === $tournament || null === $tournament->getId()) {
                    continue;
                }

                $tournamentId = $tournament->getId();
                $tournamentGroups[$tournamentId] ??= [
                    'tournament' => $tournament,
                    'matches' => [],
                ];
                $tournamentGroups[$tournamentId]['matches'][] = $match;
            }
        } else {
            if ('Tous' !== $game) {
                $criteria['game'] = $game;
            }

            $games = $gameMatchRepository->findBy($criteria, ['playedAt' => 'DESC']);
        }

        $tournamentGroups = array_values($tournamentGroups);

        return $this->renderPage($request, 'matches', 'Matchs - Naxera', [
            'games' => $games,
            'activeFilter' => $game,
            'isTournamentView' => GameMatch::GameTournament === $game,
            'matchFilters' => $matchFilters,
            'tournamentGroups' => $tournamentGroups,
        ]);
    }

    #[Route('/matches/{id}/details', name: 'app_match_details', methods: ['GET'])]
    public function details(
        GameMatch $match,
        PlayerMatchStatRepository $statRepository,
    ): Response {
        $stats = $statRepository->createQueryBuilder('s')
            ->andWhere('s.match = :match')
            ->setParameter('match', $match)
            ->getQuery()
            ->getResult();
        $statsByPlayer = [];

        foreach ($stats as $stat) {
            $statsByPlayer[$stat->getPlayer()->getId()] = $stat;
        }

        return $this->render('pages/matches/_match_details.html.twig', [
            'match' => $match,
            'stats' => $stats,
            'statsByPlayer' => $statsByPlayer,
        ]);
    }
}
