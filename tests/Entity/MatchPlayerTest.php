<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\MatchPlayer;

class MatchPlayerTest extends TestCase
{
    public function testKdRatio()
    {
        $mp = new MatchPlayer();
        $mp->setKills(10);
        $mp->setDeaths(0);
        $this->assertSame(10.0, $mp->getKdRatio());

        $mp->setKills(0);
        $mp->setDeaths(0);
        $this->assertSame(0.0, $mp->getKdRatio());

        $mp->setKills(5);
        $mp->setDeaths(2);
        $this->assertEquals(2.5, $mp->getKdRatio());
    }
}
