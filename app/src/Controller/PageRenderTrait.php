<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait PageRenderTrait
{
    /**
     * @param array<string, mixed> $parameters
     */
    private function renderPage(Request $request, string $page, string $title, array $parameters = []): Response
    {
        $template = '1' === $request->headers->get('X-Naxera-Ajax')
            ? sprintf('pages/%s/_%s.html.twig', $page, $page)
            : sprintf('pages/%s/%s.html.twig', $page, $page);

        $response = $this->render($template, $parameters + [
            'page_title' => $title,
        ]);

        $response->headers->set('X-Page-Title', $title);
        $response->headers->set('X-Hide-Footer', ($parameters['hide_footer'] ?? false) ? '1' : '0');

        return $response;
    }
}
