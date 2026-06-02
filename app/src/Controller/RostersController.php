<?php

namespace App\Controller;

use App\Entity\Roster;
use App\Repository\GameMatchRepository;
use App\Repository\PlayerMatchStatRepository;
use App\Repository\RosterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RostersController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/rosters', name: 'app_rosters')]
    public function index(Request $request, RosterRepository $rosterRepository): Response
    {
        return $this->renderPage($request, 'rosters', 'Rosters - Naxera', [
            'rosters' => $rosterRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/rosters/{id}/modal', name: 'app_rosters_modal', methods: ['GET'])]
    public function modal(
        Roster $roster,
        GameMatchRepository $matchRepository,
        PlayerMatchStatRepository $statRepository,
    ): Response {
        $matches = $matchRepository->findBy(
            ['roster' => $roster],
            ['playedAt' => 'DESC'],
            10
        );

        $playersStats = [];
        $totalKills = 0;
        $totalDeaths = 0;

        foreach ($roster->getPlayers() as $player) {
            $stats = $statRepository->createQueryBuilder('s')
                ->andWhere('s.player = :player')
                ->andWhere('s.match IN (:matches)')
                ->setParameter('player', $player)
                ->setParameter('matches', $matches)
                ->getQuery()
                ->getResult();

            $kills = 0;
            $deaths = 0;

            foreach ($stats as $stat) {
                $kills += $stat->getKills();
                $deaths += $stat->getDeaths();
            }

            $totalKills += $kills;
            $totalDeaths += $deaths;

            $playersStats[] = [
                'player' => $player,
                'kills' => $kills,
                'deaths' => $deaths,
                'kd' => $deaths > 0 ? round($kills / $deaths, 2) : $kills,
            ];
        }

        $wins = 0;
        $losses = 0;

        foreach ($matches as $match) {
            if ('Victory' === $match->getResult()) {
                ++$wins;
            }

            if ('Defeat' === $match->getResult()) {
                ++$losses;
            }
        }

        $totalMatches = $wins + $losses;

        return $this->render('pages/rosters/_roster_modal_content.html.twig', [
            'roster' => $roster,
            'matches' => $matches,
            'playersStats' => $playersStats,
            'globalStats' => [
                'kills' => $totalKills,
                'deaths' => $totalDeaths,
                'kd' => $totalDeaths > 0 ? round($totalKills / $totalDeaths, 2) : $totalKills,
                'wins' => $wins,
                'losses' => $losses,
                'winrate' => $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1).'%' : '0%',
            ],
        ]);
    }
}
