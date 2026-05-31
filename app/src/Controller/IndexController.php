<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\TeamMember;
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
        private readonly EntityManagerInterface $entityManager
    ){
    }

    #[Route('/', name: 'app_index')]
    public function index(Request $request): Response
    {
        return $this->renderPage($request, 'index', 'Naxera eSport', [
            'nbMembre' => $this->entityManager->getRepository(TeamMember::class)->findNxrNbr(),
            'matchesPlayed' => 0,
            'tournamentsWon' => 0,
            'winrate' => 0 . '%',
            'news' => [],
            'rosters' => [],
        ]);
    }
}
