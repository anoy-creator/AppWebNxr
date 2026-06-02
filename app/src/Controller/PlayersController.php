<?php

namespace App\Controller;

use App\Entity\Player;
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
        $role = $request->query->get('role');

        if ($role && 'all' !== $role) {
            $players = $playerRepository->findBy(
                ['role' => $role],
                ['pseudo' => 'ASC']
            );
        } else {
            $players = $playerRepository->findBy([], ['pseudo' => 'ASC']);
        }

        return $this->renderPage($request, 'players', 'Joueurs - Naxera', [
            'players' => $players,
        ]);
    }

    #[Route('/players/{id}/modal', name: 'app_players_modal', methods: ['GET'])]
    public function modal(Player $player): Response
    {
        return $this->render('pages/players/_player_modal_content.html.twig', [
            'player' => $player,
        ]);
    }
}
