<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/cgu', name: 'app_cgu')]
    public function cgu(Request $request): Response
    {
        return $this->renderPage($request, 'cgu', 'CGU - Naxera');
    }
}
