<?php

namespace App\Controller;

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
}
