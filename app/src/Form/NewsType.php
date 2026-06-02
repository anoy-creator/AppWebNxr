<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\GameMatch;
use App\Entity\News;
use App\Entity\Player;
use App\Entity\Roster;
use App\Form\EventType;
use App\Form\GameMatchType;
use App\Form\NewsType;
use App\Form\PlayerType;
use App\Form\RosterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/content')]
class AdminContentController extends AbstractController
{
    #[Route('/news', name: 'admin_content_news', methods: ['POST'])]
    public function news(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleForm($request, $em, new News(), NewsType::class);
    }

    #[Route('/player', name: 'admin_content_player', methods: ['POST'])]
    public function player(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleForm($request, $em, new Player(), PlayerType::class);
    }

    #[Route('/roster', name: 'admin_content_roster', methods: ['POST'])]
    public function roster(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleForm($request, $em, new Roster(), RosterType::class);
    }

    #[Route('/event', name: 'admin_content_event', methods: ['POST'])]
    public function event(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleForm($request, $em, new Event(), EventType::class);
    }

    #[Route('/match', name: 'admin_content_match', methods: ['POST'])]
    public function match(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->handleForm($request, $em, new GameMatch(), GameMatchType::class);
    }

    private function handleForm(Request $request, EntityManagerInterface $em, object $entity, string $formClass): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'Données invalides'], 400);
        }

        $form = $this->createForm($formClass, $entity);
        $form->submit($data);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = [];

            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return $this->json([
                'message' => $errors[0] ?? 'Formulaire invalide',
                'errors' => $errors,
            ], 400);
        }

        $em->persist($entity);
        $em->flush();

        return $this->json(['message' => 'Élément ajouté avec succès']);
    }
}
