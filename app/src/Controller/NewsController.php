<?php

namespace App\Controller;

use App\Entity\News;
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

    #[Route('/news/{id}/modal', name: 'app_news_modal', methods: ['GET'])]
    public function modal(News $news): Response
    {
        return $this->render('pages/news/_news_modal_content.html.twig', [
            'news' => $news,
        ]);
    }
}
