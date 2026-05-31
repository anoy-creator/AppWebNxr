<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait PageRenderTrait
{
    private function renderPage(Request $request, string $page, string $title, array $parameters = []): Response
    {
        $template = $request->headers->get('X-Naxera-Ajax') === '1'
            ? sprintf('index/_%s.html.twig', $page)
            : sprintf('index/%s.html.twig', $page);

        $response = $this->render($template, $parameters + [
            'page_title' => $title,
        ]);

        $response->headers->set('X-Page-Title', $title);
        $response->headers->set('X-Hide-Footer', ($parameters['hide_footer'] ?? false) ? '1' : '0');

        return $response;
    }
}
