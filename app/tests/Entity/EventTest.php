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

    public function testDiscordTournamentMetadataIsKeptOutOfPublicDescription(): void
    {
        $event = new Event();

        $event
            ->setDescription('Tournoi importe depuis Discord. ID bot: 1780585627517')
            ->setDiscordExternalId('1780585627517')
            ->setCheckins([
                '111111111111111111' => 'available',
                '222222222222222222' => 'unavailable',
            ])
            ->setRosterEntries([
                [
                    'discordId' => '111111111111111111',
                    'pseudo' => 'Captain One',
                    'grade' => 'Membre',
                    'role' => 'Capitaine',
                ],
            ]);

        $this->assertSame('1780585627517', $event->getDiscordExternalId());
        $this->assertSame('Tournoi importe depuis Discord.', $event->getPublicDescription());
        $this->assertSame('available', $event->getCheckins()['111111111111111111']);
        $this->assertSame('unavailable', $event->getCheckins()['222222222222222222']);
        $this->assertCount(1, $event->getRosterEntries());
    }
}
