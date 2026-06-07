<?php

namespace App\Tests\Entity;

use App\Entity\GameMatch;
use App\Entity\Player;
use App\Entity\PlayerMatchStat;
use App\Entity\Roster;
use App\Entity\Team;
use PHPUnit\Framework\TestCase;

class PlayerMatchStatTest extends TestCase
{
    public function testPlayerMatchStatBasics(): void
    {
        $player = (new Player())
            ->setPseudo('ShadowX')
            ->setAvatar('avatar.png')
            ->setRole('Joueur')
            ->setGrade('Captain')
            ->setGame('Warzone')
            ->setSocials([]);

        $teamA = (new Team())->setName('NxR');
        $teamB = (new Team())->setName('Team Horizon');

        $roster = (new Roster())
            ->setName('NxR Warzone')
            ->setGame('Call of Duty: Warzone')
            ->setBanner('banner.jpg')
            ->setWins(10)
            ->setLosses(2)
            ->setWinrate('83.3%');

        $match = (new GameMatch())
            ->setPlayedAt(new \DateTimeImmutable('2026-05-31'))
            ->setTeamA($teamA)
            ->setTeamB($teamB)
            ->setRoster($roster)
            ->setGame(GameMatch::GameWarzone)
            ->setMode(GameMatch::ModeDomination)
            ->setResult('Victory')
            ->setScore('3-1');

        $stat = new PlayerMatchStat();

        $stat
            ->setPlayer($player)
            ->setMatch($match)
            ->setKills(32)
            ->setDeaths(16);

        $this->assertNull($stat->getId());
        $this->assertSame($player, $stat->getPlayer());
        $this->assertSame($match, $stat->getMatch());
        $this->assertSame(32, $stat->getKills());
        $this->assertSame(16, $stat->getDeaths());
        $this->assertSame(2.0, $stat->getKd());
    }
}
