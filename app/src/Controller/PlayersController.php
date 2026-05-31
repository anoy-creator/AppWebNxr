<?php

namespace App\Controller;

use App\Service\SiteDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlayersController extends AbstractController
{
    use PageRenderTrait;

    public function __construct(private readonly SiteDataProvider $siteDataProvider)
    {
    }

    #[Route('/players', name: 'app_players')]
    public function index(Request $request): Response
    {
        return $this->renderPage($request, 'players', 'Joueurs - Naxera', [
            'data' => $this->siteDataProvider->getData(),
        ]);
    }
}
