<?php

namespace App\Tests\Entity;

use App\Entity\Team;
use PHPUnit\Framework\TestCase;

class TeamTest extends TestCase
{
    public function testTeamBasics(): void
    {
        $team = new Team();

        $team->setName('NxR');

        $this->assertNull($team->getId());
        $this->assertSame('NxR', $team->getName());
    }
}
