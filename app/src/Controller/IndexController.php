<?php

namespace App\Controller;

use App\Repository\GameMatchRepository;
use App\Repository\NewsRepository;
use App\Repository\PlayerRepository;
use App\Repository\RosterRepository;
use App\Service\SiteDataProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    use PageRenderTrait;

    public function __construct(
        private readonly SiteDataProvider $siteDataProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: 'app_index')]
    public function index(
        Request $request,
        PlayerRepository $playerRepository,
        GameMatchRepository $gameMatchRepository,
        NewsRepository $newsRepository,
        RosterRepository $rosterRepository,
    ): Response {
        $players = $playerRepository->findAll();
        $matches = $gameMatchRepository->findAll();

        $matchesPlayed = count($matches);

        $victories = count(array_filter(
            $matches,
            static fn ($match) => 'Victory' === $match->getResult()
        ));

        $winrate = $matchesPlayed > 0
            ? number_format(($victories / $matchesPlayed) * 100, 1).'%'
            : '0%';

        return $this->renderPage($request, 'index', 'Naxera eSport', [
            'nbMembre' => count($players),
            'matchesPlayed' => $matchesPlayed,
            'tournamentsWon' => 0,
            'winrate' => $winrate,
            'news' => $newsRepository->findBy([], ['date' => 'DESC'], 3),
            'rosters' => $rosterRepository->findAll(),
        ]);
    }
}
