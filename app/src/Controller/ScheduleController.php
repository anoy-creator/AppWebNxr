<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\GameMatchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ScheduleController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/schedule', name: 'app_schedule')]
    public function index(
        Request $request,
        EventRepository $eventRepository,
        GameMatchRepository $gameMatchRepository,
    ): Response {
        return $this->renderPage($request, 'schedule', 'Planning - Naxera', [
            'events' => $eventRepository->findBy([], ['date' => 'ASC']),
            'matches' => $gameMatchRepository->findBy([], ['playedAt' => 'ASC']),
        ]);
    }
}
