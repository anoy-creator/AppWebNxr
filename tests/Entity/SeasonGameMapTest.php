<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Season;
use App\Entity\GameMap;

class SeasonGameMapTest extends TestCase
{
    public function testCollectionsInitialized()
    {
        $season = new Season();
        $this->assertIsObject($season->getGames());
        $this->assertCount(0, $season->getGames());

        $map = new GameMap();
        $this->assertIsObject($map->getGames());
        $this->assertCount(0, $map->getGames());
    }
}
