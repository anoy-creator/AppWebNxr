<?php

namespace App\Tests\Entity;

use App\Entity\Player;
use PHPUnit\Framework\TestCase;

class PlayerTest extends TestCase
{
    public function testPlayerBasics(): void
    {
        $player = new Player();

        $player
            ->setPseudo('ShadowX')
            ->setDiscordId('123456789')
            ->setAvatar('avatar.png')
            ->setRole('Joueur')
            ->setGrade('Captain')
            ->setGame('COD')
            ->setSocials(['twitter' => 'shadowx']);

        $this->assertNull($player->getId());
        $this->assertSame('ShadowX', $player->getPseudo());
        $this->assertSame('123456789', $player->getDiscordId());
        $this->assertSame('avatar.png', $player->getAvatar());
        $this->assertSame('Joueur', $player->getRole());
        $this->assertSame('Captain', $player->getGrade());
        $this->assertSame('COD', $player->getGame());
        $this->assertSame(['twitter' => 'shadowx'], $player->getSocials());
    }
}
