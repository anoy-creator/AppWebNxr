<?php

namespace App\Controller;

use App\Service\SiteDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ScheduleController extends AbstractController
{
    use PageRenderTrait;

    public function __construct(private readonly SiteDataProvider $siteDataProvider)
    {
    }

    #[Route('/schedule', name: 'app_schedule')]
    public function index(Request $request): Response
    {
        return $this->renderPage($request, 'schedule', 'Planning - Naxera', [
            'data' => $this->siteDataProvider->getData(),
        ]);
    }
}
