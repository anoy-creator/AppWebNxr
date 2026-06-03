<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\GameMatchRepository;
use App\Repository\NewsRepository;
use App\Repository\PlayerRepository;
use App\Repository\RosterRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/', name: 'app_index')]
    public function index(
        Request $request,
        PlayerRepository $playerRepository,
        GameMatchRepository $gameMatchRepository,
        NewsRepository $newsRepository,
        RosterRepository $rosterRepository,
        TeamRepository $teamRepository,
        EventRepository $eventRepository,
        UserRepository $userRepository,
    ): Response {
        $players = $playerRepository->findAll();
        $users = $userRepository->findAll();
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

            'players' => $players,
            'users' => $users,
            'teams' => $teamRepository->findAll(),
            'tournaments' => $eventRepository->findBy(
                ['type' => 'tournament'],
                ['date' => 'DESC']
            ),
        ]);
    }
}
