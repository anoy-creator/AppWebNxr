<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewsController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/news', name: 'app_news')]
    public function index(Request $request, NewsRepository $newsRepository): Response
    {
        return $this->renderPage($request, 'news', 'Actualites - Naxera', [
            'news' => $newsRepository->findBy([], ['date' => 'DESC']),
        ]);
    }
}
