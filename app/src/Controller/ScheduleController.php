<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\GameMatch;
use App\Repository\EventRepository;
use App\Repository\GameMatchRepository;
use App\Repository\PlayerMatchStatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ScheduleController extends AbstractController
{
    use PageRenderTrait;

    #[Route('/schedule', name: 'app_schedule')]
    public function index(
        Request $request,
        EventRepository $eventRepository,
        GameMatchRepository $gameMatchRepository,
    ): Response {
        $hasTypeFilter = $request->query->has('types');
        $types = $hasTypeFilter ? $request->query->all('types') : Event::ScheduleTypes;
        $types = array_values(array_intersect($types, Event::ScheduleTypes));

        $eventTypes = array_values(array_intersect($types, Event::EventTypes));

        $events = [];

        if (!empty($eventTypes)) {
            $events = $eventRepository->createQueryBuilder('e')
                ->andWhere('e.type IN (:types)')
                ->setParameter('types', $eventTypes)
                ->orderBy('e.date', 'DESC')
                ->addOrderBy('e.time', 'DESC')
                ->getQuery()
                ->getResult();
        }

        $matches = [];

        if (in_array(Event::MatchOfficiel, $types, true)) {
            $matches = $gameMatchRepository->findBy([], [
                'playedAt' => 'DESC',
            ]);
        }

        $scheduleItems = [];

        foreach ($events as $event) {
            $scheduleItems[] = [
                'kind' => 'event',
                'event' => $event,
                'sortAt' => $this->createEventSortDate($event),
            ];
        }

        foreach ($matches as $match) {
            $scheduleItems[] = [
                'kind' => 'match',
                'match' => $match,
                'sortAt' => $match->getPlayedAt() ?? new \DateTimeImmutable('@0'),
            ];
        }

        usort(
            $scheduleItems,
            static fn (array $left, array $right): int => $right['sortAt'] <=> $left['sortAt']
        );

        $scheduleFilters = array_map(
            static fn (string $type) => [
                'value' => $type,
                'label' => Event::TypeLabels[$type],
                'color' => Event::TypeColors[$type],
            ],
            Event::ScheduleTypes
        );

        return $this->renderPage($request, 'schedule', 'Planning - Naxera', [
            'events' => $events,
            'matches' => $matches,
            'scheduleItems' => $scheduleItems,
            'activeTypes' => $types,
            'scheduleFilters' => $scheduleFilters,
        ]);
    }

    #[Route('/schedule/event/{id}/details', name: 'app_schedule_event_details', methods: ['GET'])]
    public function eventDetails(Event $event): Response
    {
        return $this->render('pages/schedule/_scheduleEvent_details.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/schedule/match/{id}/details', name: 'app_schedule_match_details', methods: ['GET'])]
    public function matchDetails(
        GameMatch $match,
        PlayerMatchStatRepository $statRepository,
    ): Response {
        $stats = $statRepository->findBy([
            'match' => $match,
        ]);

        return $this->render('pages/schedule/_scheduleMatch_details.html.twig', [
            'match' => $match,
            'stats' => $stats,
        ]);
    }

    private function createEventSortDate(Event $event): \DateTimeImmutable
    {
        $date = $event->getDate()?->format('Y-m-d') ?? '1970-01-01';
        $time = $event->getTime() ?: '00:00';

        return new \DateTimeImmutable($date.' '.$time);
    }
}
