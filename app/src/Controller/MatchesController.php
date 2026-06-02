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
    public function index(Request $request, GameMatchRepository $gameMatchRepository): Response
    {
        $game = $request->query->get('game');

        $criteria = [];

        if ($game && 'Tous' !== $game) {
            $criteria['game'] = $game;
        }

        $games = $gameMatchRepository->findBy($criteria, ['playedAt' => 'DESC']);

        return $this->renderPage($request, 'matches', 'Matchs - Naxera', [
            'games' => $games,
            'activeFilter' => $game ?: 'Tous',
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

        return $this->render('pages/matches/_match_details.html.twig', [
            'match' => $match,
            'stats' => $stats,
        ]);
    }
}
