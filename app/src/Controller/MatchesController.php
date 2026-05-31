<?php

namespace App\Controller;

use App\Repository\GameMatchRepository;
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
        $games = $gameMatchRepository->findBy(
            [],
            ['playedAt' => 'DESC']
        );

        return $this->renderPage($request, 'matches', 'Matchs - Naxera', [
            'games' => $games,
        ]);
    }
}
