<?php

namespace App\Tests\Entity;

use App\Entity\GameMatch;
use App\Entity\Roster;
use App\Entity\Team;
use PHPUnit\Framework\TestCase;

class GameMatchTest extends TestCase
{
    public function testGameMatchBasics(): void
    {
        $teamA = (new Team())->setName('NxR');
        $teamB = (new Team())->setName('Team Horizon');

        $roster = (new Roster())
            ->setName('NxR Warzone')
            ->setGame('Call of Duty: Warzone')
            ->setBanner('banner.jpg')
            ->setWins(10)
            ->setLosses(2)
            ->setWinrate('83.3%');

        $playedAt = new \DateTimeImmutable('2026-05-31');

        $match = new GameMatch();

        $match
            ->setPlayedAt($playedAt)
            ->setTeamA($teamA)
            ->setTeamB($teamB)
            ->setRoster($roster)
            ->setGame(GameMatch::GameHardcore)
            ->setMode(GameMatch::ModeHardpoint)
            ->setResult('Victory')
            ->setScore('3-1');

        $this->assertNull($match->getId());
        $this->assertSame($playedAt, $match->getPlayedAt());
        $this->assertSame($teamA, $match->getTeamA());
        $this->assertSame($teamB, $match->getTeamB());
        $this->assertSame($roster, $match->getRoster());
        $this->assertSame(GameMatch::GameHardcore, $match->getGame());
        $this->assertSame(GameMatch::ModeHardpoint, $match->getMode());
        $this->assertSame('Victory', $match->getResult());
        $this->assertSame('3-1', $match->getScore());
    }

    public function testModeMustBeInClosedList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new GameMatch())->setMode('Battle Royale');
    }
}
