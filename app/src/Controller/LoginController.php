<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/login', name: 'app_login')]
    public function index(Request $request): Response
    {
        return $this->renderPage($request, 'login', 'Connexion - Naxera', [
            'hide_footer' => true,
        ]);
    }
}
