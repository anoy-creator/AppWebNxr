<?php

namespace App\Tests\Entity;

use App\Entity\Roster;
use PHPUnit\Framework\TestCase;

class RosterTest extends TestCase
{
    public function testRosterBasics(): void
    {
        $roster = new Roster();

        $roster
            ->setName('NxR Warzone')
            ->setGame('Warzone')
            ->setBanner('banner.jpg')
            ->setWins(10)
            ->setLosses(2)
            ->setWinrate('83.3%');

        $this->assertNull($roster->getId());
        $this->assertSame('NxR Warzone', $roster->getName());
        $this->assertSame('Warzone', $roster->getGame());
        $this->assertSame('banner.jpg', $roster->getBanner());
        $this->assertSame(10, $roster->getWins());
        $this->assertSame(2, $roster->getLosses());
        $this->assertSame('83.3%', $roster->getWinrate());
    }
}
