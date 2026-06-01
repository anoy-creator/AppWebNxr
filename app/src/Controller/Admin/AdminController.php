<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\GameMatch;
use App\Entity\News;
use App\Entity\Player;
use App\Entity\Roster;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/news/create', name: 'news_create', methods: ['POST'])]
    public function createNews(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $news = new News();
        $news->setTitle($data['title'] ?? '')
            ->setAuthor($data['author'] ?? '')
            ->setExcerpt($data['excerpt'] ?? '')
            ->setContent($data['content'] ?? '')
            ->setImage($data['image'] ?? '')
            ->setDate(new \DateTimeImmutable());

        $em->persist($news);
        $em->flush();

        return $this->json(['message' => 'Actualité créée'], 201);
    }

    #[Route('/players/create', name: 'players_create', methods: ['POST'])]
    public function createPlayer(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $player = new Player();
        $player->setPseudo($data['pseudo'] ?? '')
            ->setRole($data['role'] ?? '')
            ->setGrade($data['grade'] ?? '')
            ->setGame($data['game'] ?? '')
            ->setAvatar($data['avatar'] ?? '');

        $em->persist($player);
        $em->flush();

        return $this->json(['message' => 'Joueur créé'], 201);
    }

    #[Route('/rosters/create', name: 'rosters_create', methods: ['POST'])]
    public function createRoster(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $roster = new Roster();
        $roster->setName($data['name'] ?? '')
            ->setGame($data['game'] ?? '')
            ->setWins($data['wins'] ?? 0)
            ->setLosses($data['losses'] ?? 0)
            ->setBanner($data['banner'] ?? '')
            ->setWinrate('0%');

        $em->persist($roster);
        $em->flush();

        return $this->json(['message' => 'Roster créé'], 201);
    }

    #[Route('/events/create', name: 'events_create', methods: ['POST'])]
    public function createEvent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $event = new Event();
        $event->setTitle($data['title'] ?? '')
            ->setType($data['type'] ?? 'training')
            ->setDate(new \DateTimeImmutable($data['date']))
            ->setTime($data['time'] ?? '00:00')
            ->setDescription($data['description'] ?? '');

        $em->persist($event);
        $em->flush();

        return $this->json(['message' => 'Event créé'], 201);
    }

    #[Route('/matches/create', name: 'matches_create', methods: ['POST'])]
    public function createMatch(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $match = new GameMatch();
        $match->setPlayedAt(new \DateTimeImmutable($data['playedAt']))
            ->setMode($data['mode'] ?? '')
            ->setScore($data['score'] ?? '0-0');

        $em->persist($match);
        $em->flush();

        return $this->json(['message' => 'Match créé'], 201);
    }
}
