<?php

namespace App\Tests\Functional;

use App\Entity\Event;
use App\Entity\GameMatch;
use App\Entity\News;
use App\Entity\Player;
use App\Entity\Roster;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminLoginTest extends WebTestCase
{
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

    public function testAdminSessionSurvivesAfterFormLogin(): void
    {
        $user = new User();
        $user
            ->setDiscordId('admin-functional')
            ->setDiscordName('Admin Functional')
            ->setUsername('admin')
            ->setRoles(['ROLE_ADMIN']);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'secret-password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('POST', '/login/admin', [
            '_username' => 'admin',
            '_password' => 'secret-password',
        ]);

        self::assertResponseRedirects();

        $this->client->request('GET', '/admin/content/modal/add-news');

        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/content/modal/add-player');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="socials[twitter]"]');
        self::assertSelectorNotExists('input[name="socials"]');

        $this->client->request('GET', '/admin/content/modal/add-event');

        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/content/modal/add-match');

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanCreateNewsWithUploadedImage(): void
    {
        $user = new User();
        $user
            ->setDiscordId('admin-upload')
            ->setDiscordName('Admin Upload')
            ->setUsername('admin-upload')
            ->setRoles(['ROLE_ADMIN']);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'secret-password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $tmpFile = tempnam(sys_get_temp_dir(), 'news-upload-');
        self::assertIsString($tmpFile);
        file_put_contents($tmpFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

        $this->client->request(
            'POST',
            '/admin/content/news',
            [
                'title' => 'Actualite upload',
                'author' => 'Admin',
                'date' => '2026-06-06',
                'excerpt' => 'Resume upload',
                'content' => 'Contenu upload',
            ],
            [
                'imageFile' => new UploadedFile($tmpFile, 'news.png', 'image/png', null, true),
            ],
            [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ]
        );

        self::assertResponseIsSuccessful();

        $news = $this->entityManager->getRepository(News::class)->findOneBy([
            'title' => 'Actualite upload',
        ]);

        self::assertInstanceOf(News::class, $news);
        self::assertStringStartsWith('/uploads/news/', $news->getImage());

        $uploadedPath = static::getContainer()->getParameter('kernel.project_dir').'/public'.$news->getImage();

        self::assertFileExists($uploadedPath);
        @unlink($uploadedPath);
    }

    public function testAdminCanCreateMatchWithoutRosterField(): void
    {
        $user = new User();
        $user
            ->setDiscordId('admin-match')
            ->setDiscordName('Admin Match')
            ->setUsername('admin-match')
            ->setRoles(['ROLE_ADMIN']);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'secret-password'));

        $teamA = (new Team())->setName('NxR');
        $teamB = (new Team())->setName('Team Horizon');
        $roster = (new Roster())
            ->setName('NxR CDL')
            ->setGame('CDL')
            ->setBanner('banner.jpg')
            ->setWins(0)
            ->setLosses(0)
            ->setWinrate('0%');

        $this->entityManager->persist($user);
        $this->entityManager->persist($teamA);
        $this->entityManager->persist($teamB);
        $this->entityManager->persist($roster);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request(
            'POST',
            '/admin/content/match',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
            json_encode([
                'playedAt' => '2026-06-07',
                'playedTime' => '21:00',
                'teamA' => $teamA->getId(),
                'teamB' => $teamB->getId(),
                'game' => GameMatch::GameCdl,
                'mode' => GameMatch::ModeHardpoint,
                'score' => '250-200',
                'result' => 'Victory',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $match = $this->entityManager->getRepository(GameMatch::class)->findOneBy([
            'teamA' => $teamA,
            'teamB' => $teamB,
        ]);

        self::assertInstanceOf(GameMatch::class, $match);
        self::assertSame($roster, $match->getRoster());
    }

    public function testLogoutRedirectsToHome(): void
    {
        $user = new User();
        $user
            ->setDiscordId('admin-logout')
            ->setDiscordName('Admin Logout')
            ->setUsername('admin-logout')
            ->setRoles(['ROLE_ADMIN']);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'secret-password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/logout');

        self::assertResponseRedirects('/');
    }

    public function testProfileSocialLinksKeepOnlyAllowedUrls(): void
    {
        $user = new User();
        $user
            ->setDiscordId('374571291539144727')
            ->setDiscordName('Admin Social')
            ->setUsername('admin-social')
            ->setRoles(['ROLE_ADMIN']);

        $player = new Player();
        $player
            ->setPseudo('Admin Social')
            ->setDiscordId('374571291539144727')
            ->setAvatar('avatar.png')
            ->setRole('Staff')
            ->setGrade('Admin')
            ->setGame('All Games')
            ->setSocials(['discord' => '374571291539144727']);

        $user->setPlayer($player);

        $this->entityManager->persist($player);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request(
            'POST',
            '/ajax/profile/socials',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
            json_encode([
                'socials' => [
                    'twitter' => 'https://twitter.com/admin_nxr',
                    'youtube' => 'https://youtube.com/@nxr_official',
                    'discord' => 'https://discord.gg/naxera',
                    'twitch' => 'https://twitch.tv/admin_nxr',
                ],
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        self::assertSame([
            'youtube' => 'https://youtube.com/@nxr_official',
            'discord' => 'https://discord.gg/naxera',
            'twitter' => 'https://twitter.com/admin_nxr',
        ], $player->getSocials());
    }

    public function testUserCanDeleteProfileAndErasePersonalData(): void
    {
        $user = new User();
        $user
            ->setDiscordId('profile-delete')
            ->setDiscordName('Profile Delete')
            ->setUsername('profile-delete')
            ->setEmail('delete@example.com')
            ->setAvatar('avatar.png')
            ->setRoles(['ROLE_USER']);

        $player = new Player();
        $player
            ->setPseudo('Profile Delete')
            ->setDiscordId('profile-delete')
            ->setAvatar('avatar.png')
            ->setRole('Staff')
            ->setGrade('Admin')
            ->setGame('Call of Duty')
            ->setSocials([
                'discord' => 'profile-delete',
                'twitter' => 'https://twitter.com/profile_delete',
            ]);

        $event = new Event();
        $event
            ->setTitle('Tournoi test')
            ->setType(Event::Tournoi)
            ->setDate(new \DateTimeImmutable('2026-06-10 20:00:00'))
            ->setTime('20:00')
            ->setDescription('Tournoi de test')
            ->setTournamentFormat('4v4')
            ->setCheckins([
                'profile-delete' => 'available',
                'other-player' => 'unavailable',
            ])
            ->setRosterEntries([
                [
                    'discordId' => 'profile-delete',
                    'pseudo' => 'Profile Delete',
                    'grade' => 'Admin',
                    'role' => 'Titulaire',
                ],
                [
                    'discordId' => 'other-player',
                    'pseudo' => 'Other Player',
                    'grade' => 'Joueur',
                    'role' => 'Titulaire',
                ],
            ]);

        $user->setPlayer($player);

        $this->entityManager->persist($player);
        $this->entityManager->persist($user);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $userId = $user->getId();
        $playerId = $player->getId();
        $eventId = $event->getId();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/ajax/profile', [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        self::assertResponseIsSuccessful();

        $token = $crawler->filter('[data-profile-delete-form]')->attr('data-token');
        self::assertNotEmpty($token);

        $this->client->request(
            'POST',
            '/ajax/profile/delete',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
            json_encode(['_token' => $token], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $this->entityManager->clear();

        self::assertNull($this->entityManager->getRepository(User::class)->find($userId));

        $erasedPlayer = $this->entityManager->getRepository(Player::class)->find($playerId);
        self::assertInstanceOf(Player::class, $erasedPlayer);
        self::assertSame('Profil supprime', $erasedPlayer->getPseudo());
        self::assertSame('', $erasedPlayer->getAvatar());
        self::assertNull($erasedPlayer->getDiscordId());
        self::assertSame([], $erasedPlayer->getSocials());

        $updatedEvent = $this->entityManager->getRepository(Event::class)->find($eventId);
        self::assertInstanceOf(Event::class, $updatedEvent);
        self::assertSame(['other-player' => 'unavailable'], $updatedEvent->getCheckins());
        self::assertSame([
            [
                'discordId' => 'other-player',
                'pseudo' => 'Other Player',
                'grade' => 'Joueur',
                'role' => 'Titulaire',
            ],
        ], $updatedEvent->getRosterEntries());
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
