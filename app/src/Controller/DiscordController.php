<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscordController extends AbstractController
{
    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectCheckAction(Request $request): Response
    {
        return $this->redirectToRoute('app_home');
    }
}
