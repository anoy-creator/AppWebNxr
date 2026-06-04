<?php

namespace App\Tests\Functional;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DiscordTournamentSyncTest extends WebTestCase
{
    private const ApiKey = 'test-api-key';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->purgeDatabase();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->purgeDatabase();
        }

        parent::tearDown();
    }

    public function testDiscordTournamentSyncCreatesRosterAndPresenceSnapshot(): void
    {
        $payload = [
            'id' => 'bot-functional-001',
            'botTournoiId' => 'bot-functional-001',
            'status' => 'active',
            'captain' => '111111111111111111',
            'date' => '10/06/2026',
            'heure' => '21:00',
            'format' => '3v3',
            'players' => [
                '222222222222222222',
                '333333333333333333',
            ],
            'substitutes' => [
                '444444444444444444',
            ],
            'checkins' => [
                '111111111111111111' => 'available',
                '222222222222222222' => 'available',
                '333333333333333333' => 'unavailable',
            ],
            'members' => [
                '111111111111111111' => [
                    'username' => 'captain_one',
                    'displayName' => 'Captain One',
                ],
                '222222222222222222' => [
                    'username' => 'player_one',
                    'displayName' => 'Player One',
                ],
                '333333333333333333' => [
                    'username' => 'player_two',
                    'displayName' => 'Player Two',
                ],
                '444444444444444444' => [
                    'username' => 'sub_one',
                    'displayName' => 'Sub One',
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/discord/add-tournois',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_API_KEY' => self::ApiKey,
            ],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $event = $this->entityManager
            ->getRepository(Event::class)
            ->findOneBy(['discordExternalId' => 'bot-functional-001']);

        self::assertInstanceOf(Event::class, $event);
        self::assertSame('Tournoi 3v3', $event->getTitle());
        self::assertSame('Tournoi importe depuis Discord.', $event->getDescription());
        self::assertSame('Tournoi importe depuis Discord.', $event->getPublicDescription());
        self::assertSame('bot-functional-001', $event->getDiscordExternalId());
        self::assertSame('Captain One', $event->getCaptain()?->getPseudo());
        self::assertCount(2, $event->getPlayers());
        self::assertCount(1, $event->getSubstitutes());
        self::assertCount(4, $event->getRosterEntries());

        $this->client->request('GET', sprintf('/schedule/event/%d/details', $event->getId()));

        self::assertResponseIsSuccessful();

        $html = (string) $this->client->getResponse()->getContent();

        self::assertStringContainsString('Captain One', $html);
        self::assertStringContainsString('Player One', $html);
        self::assertStringContainsString('Player Two', $html);
        self::assertStringContainsString('Sub One', $html);
        self::assertStringContainsString('4 joueur(s)', $html);
        self::assertStringContainsString('✓', $html);
        self::assertStringContainsString('✕', $html);
        self::assertStringContainsString('?', $html);
        self::assertStringNotContainsString('ID bot', $html);
        self::assertStringNotContainsString('bot-functional-001', $html);
    }

    private function purgeDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $tables = [
            'PlayerMatchStat',
            'game_match_players',
            'game_match_substitutes',
            'GameMatch',
            'event_players',
            'event_substitutes',
            'Event',
            'News',
            'user',
            'Player',
            'Roster',
            'Team',
        ];

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $connection->executeStatement(sprintf('DELETE FROM `%s`', $table));
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }
}
