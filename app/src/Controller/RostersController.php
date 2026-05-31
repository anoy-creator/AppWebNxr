<?php

namespace App\Controller;

use App\Service\SiteDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RostersController extends AbstractController
{
    use PageRenderTrait;

    public function __construct(private readonly SiteDataProvider $siteDataProvider)
    {
    }

    #[Route('/rosters', name: 'app_rosters')]
    public function index(Request $request): Response
    {
        return $this->renderPage($request, 'rosters', 'Rosters - Naxera', [
            'data' => $this->siteDataProvider->getData(),
        ]);
    }
}
