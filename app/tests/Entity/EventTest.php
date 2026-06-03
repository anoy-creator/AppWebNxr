<?php

namespace App\Tests\Entity;

use App\Entity\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testEventBasics(): void
    {
        $event = new Event();
        $date = new \DateTimeImmutable('2026-06-01');

        $event
            ->setTitle('Training')
            ->setType('training')
            ->setTournamentFormat('2v2')
            ->setDate($date)
            ->setTime('19:00')
            ->setDescription('Session');

        $this->assertNull($event->getId());
        $this->assertSame('Training', $event->getTitle());
        $this->assertSame('training', $event->getType());
        $this->assertSame('2v2', $event->getTournamentFormat());
        $this->assertSame($date, $event->getDate());
        $this->assertSame('19:00', $event->getTime());
        $this->assertSame('Session', $event->getDescription());
    }
}
