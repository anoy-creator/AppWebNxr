<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return $this->redirectToRoute('hwi_oauth_service_redirect', [
            'service' => 'discord',
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony gère tout seul
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        return $this->render('page/accueil.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/connect/failure', name: 'connect_failure')]
    public function failure(): RedirectResponse
    {
        $this->addFlash('error', 'Connexion Discord échouée !');

        return $this->redirectToRoute('homepage');
    }
}
