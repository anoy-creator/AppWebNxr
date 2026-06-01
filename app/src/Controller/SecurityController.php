<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
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

        return $this->redirectToRoute('app_index');
    }

    #[Route('/ajax/profile', name: 'ajax_profile')]
    public function profileAjax(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        return $this->render('pages/profile/profile.html.twig', [
            'user' => $user,
        ]);
    }
}
