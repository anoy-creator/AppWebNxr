<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/admin', name: 'app_admin')]
    public function index(Request $request): Response
    {
        return $this->renderPage($request, 'admin', 'Admin - Naxera', [
            'hide_footer' => true,
        ]);
    }
}
