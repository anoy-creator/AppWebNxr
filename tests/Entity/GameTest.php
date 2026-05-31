<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Game;
use App\Entity\MatchPlayer;

class GameTest extends TestCase
{
    public function testAddRemoveMatchPlayer()
    {
        $game = new Game();
        $mp = new MatchPlayer();
        $game->addMatchPlayer($mp);
        $this->assertSame($game, $mp->getGame());
        $this->assertCount(1, $game->getMatchPlayers());

        $game->removeMatchPlayer($mp);
        $this->assertNull($mp->getGame());
        $this->assertCount(0, $game->getMatchPlayers());
    }
}
