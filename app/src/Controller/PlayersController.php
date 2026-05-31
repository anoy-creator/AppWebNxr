<?php

namespace App\Controller;

use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlayersController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/players', name: 'app_players')]
    public function index(Request $request, PlayerRepository $playerRepository): Response
    {
        return $this->renderPage($request, 'players', 'Joueurs - Naxera', [
            'players' => $playerRepository->findBy([], ['pseudo' => 'ASC']),
        ]);
    }
}
