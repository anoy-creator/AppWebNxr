<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\GameMatch;
use App\Entity\News;
use App\Entity\Player;
use App\Entity\Roster;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Crée les rosters d'abord
        $rosters = [];

        $rostersData = [
            [
                'name' => 'NxR Warzone',
                'game' => 'Call of Duty: Warzone',
                'banner' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1400',
                'wins' => 47,
                'losses' => 13,
                'winrate' => '78.3%',
            ],
            [
                'name' => 'NxR CDL',
                'game' => 'Call of Duty League',
                'banner' => 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=1400',
                'wins' => 32,
                'losses' => 18,
                'winrate' => '64.0%',
            ],
        ];

        foreach ($rostersData as $data) {
            $roster = new Roster();

            $roster
                ->setName($data['name'])
                ->setGame($data['game'])
                ->setBanner($data['banner'])
                ->setWins($data['wins'])
                ->setLosses($data['losses'])
                ->setWinrate($data['winrate']);

            $manager->persist($roster);

            $rosters[$data['name']] = $roster;
        }

        // Admin user
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@nxr.com');
        $admin->setDiscordId('admin-fixture');
        $admin->setDiscordName('Admin NxR');
        $admin->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'adminNxr');
        $admin->setPassword($hashedPassword);

        $manager->persist($admin);

        // Admin joueur
        $adminPlayer = new Player();
        $adminPlayer
            ->setPseudo('Admin Pro')
            ->setAvatar('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=700')
            ->setRole('Admin')
            ->setGrade('Founder')
            ->setGame('All Games')
            ->setRoster($rosters['NxR Warzone'])
            ->setSocials(['twitter' => 'admin_nxr', 'twitch' => 'admin_nxr', 'youtube' => 'nxr_official']);

        $manager->persist($adminPlayer);

        // Autres joueurs
        $playersData = [
            ['pseudo' => 'ShadowX', 'avatar' => 'https://images.unsplash.com/photo-1511367461989-f85a21fda167?w=700', 'role' => 'Joueur', 'grade' => 'Captain', 'game' => 'Call of Duty', 'roster' => 'NxR Warzone', 'socials' => ['twitter' => 'shadowx', 'twitch' => 'shadowx_nxr']],
            ['pseudo' => 'NeonKnight', 'avatar' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=700', 'role' => 'Joueur', 'grade' => 'Pro Player', 'game' => 'Warzone', 'roster' => 'NxR Warzone', 'socials' => ['twitch' => 'neonknight', 'youtube' => 'neonknight']],
            ['pseudo' => 'VortexPro', 'avatar' => 'https://images.unsplash.com/photo-1509967419530-da38b4704bc6?w=700', 'role' => 'Streamer', 'grade' => 'Content Creator', 'game' => 'Multi-Gaming', 'roster' => 'NxR Warzone', 'socials' => ['twitter' => 'vortexpro', 'twitch' => 'vortexpro', 'youtube' => 'vortex']],
            ['pseudo' => 'QuantumAce', 'avatar' => 'https://images.unsplash.com/photo-1560253023-3ec5d502959f?w=700', 'role' => 'Joueur', 'grade' => 'Pro Player', 'game' => 'Call of Duty', 'roster' => 'NxR CDL', 'socials' => ['twitter' => 'quantumace']],
            ['pseudo' => 'BlazeFury', 'avatar' => 'https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=700', 'role' => 'Staff', 'grade' => 'Coach', 'game' => 'All Games', 'roster' => 'NxR CDL', 'socials' => ['twitter' => 'blazefury']],
            ['pseudo' => 'CyberStorm', 'avatar' => 'https://images.unsplash.com/photo-1552374196-1ab2a1c593e8?w=700', 'role' => 'Createur', 'grade' => 'Video Editor', 'game' => 'Content', 'roster' => 'NxR CDL', 'socials' => ['twitter' => 'cyberstorm', 'youtube' => 'cyberstorm']],
        ];

        foreach ($playersData as $data) {
            $player = new Player();

            $player
                ->setPseudo($data['pseudo'])
                ->setAvatar($data['avatar'])
                ->setRole($data['role'])
                ->setGrade($data['grade'])
                ->setGame($data['game'])
                ->setRoster($rosters[$data['roster']])
                ->setSocials($data['socials']);

            $manager->persist($player);
        }

        $teamNames = [
            'NxR Esport',
            'Team Horizon',
            'Elite Squad',
            'Phantom Gaming',
            'Thunder Esports',
            'Apex Legends',
            'Team Vitality',
        ];

        $teams = [];

        foreach ($teamNames as $teamName) {
            $team = new Team();
            $team->setName($teamName);

            $manager->persist($team);
            $teams[$teamName] = $team;
        }

        $matchesData = [
            ['date' => '2026-05-28', 'teamA' => 'NxR Esport', 'teamB' => 'Team Horizon', 'mode' => 'Battle Royale', 'result' => 'Victory', 'score' => '3-1', 'game' => 'Warzone'],
            ['date' => '2026-05-25', 'teamA' => 'NxR Esport', 'teamB' => 'Elite Squad', 'mode' => 'Hardpoint', 'result' => 'Victory', 'score' => '250-187', 'game' => 'CDL'],
            ['date' => '2026-05-22', 'teamA' => 'NxR Esport', 'teamB' => 'Phantom Gaming', 'mode' => 'Search & Destroy', 'result' => 'Defeat', 'score' => '4-6', 'game' => 'CDL'],
            ['date' => '2026-05-20', 'teamA' => 'NxR Esport', 'teamB' => 'Thunder Esports', 'mode' => 'Resurgence', 'result' => 'Victory', 'score' => '5-0', 'game' => 'Warzone'],
            ['date' => '2026-05-18', 'teamA' => 'NxR Esport', 'teamB' => 'Apex Legends', 'mode' => 'Tournament Finals', 'result' => 'Victory', 'score' => '3-2', 'game' => 'Tournament'],
        ];

        foreach ($matchesData as $data) {
            $match = new GameMatch();

            $match
                ->setPlayedAt(new \DateTimeImmutable($data['date']))
                ->setTeamA($teams[$data['teamA']])
                ->setTeamB($teams[$data['teamB']])
                ->setMode($data['mode'])
                ->setResult($data['result'])
                ->setScore($data['score'])
                ->setGame($data['game']);

            $manager->persist($match);
        }

        $newsData = [
            ['title' => 'NxR remporte le championnat Warzone Spring 2026', 'author' => 'BlazeFury', 'date' => '2026-05-27', 'image' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=900', 'excerpt' => 'Une victoire ecrasante pour notre roster Warzone qui domine la competition.', 'content' => 'Notre equipe Warzone a brille lors du championnat Spring 2026 en remportant le titre avec une performance exceptionnelle.'],
            ['title' => 'Nouveau partenariat avec HyperX', 'author' => 'CyberStorm', 'date' => '2026-05-20', 'image' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=900', 'excerpt' => 'NxR annonce un partenariat strategique avec HyperX pour equiper tous nos joueurs.', 'content' => 'Nous sommes fiers d annoncer notre nouveau partenariat avec HyperX. Tous nos joueurs seront equipes du meilleur materiel gaming.'],
            ['title' => 'Recrutement: nous recherchons des talents', 'author' => 'BlazeFury', 'date' => '2026-05-15', 'image' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=900', 'excerpt' => 'NxR ouvre ses portes a de nouveaux talents pour renforcer ses rosters.', 'content' => 'Vous pensez avoir le niveau pour rejoindre NxR ? Nous recherchons activement de nouveaux joueurs talentueux pour nos equipes Warzone et CDL.'],
        ];

        foreach ($newsData as $data) {
            $news = new News();

            $news
                ->setTitle($data['title'])
                ->setAuthor($data['author'])
                ->setDate(new \DateTimeImmutable($data['date']))
                ->setImage($data['image'])
                ->setExcerpt($data['excerpt'])
                ->setContent($data['content']);

            $manager->persist($news);
        }

        $eventsData = [
            ['title' => 'Entrainement CDL', 'type' => 'training', 'date' => '2026-05-30', 'time' => '19:00', 'description' => 'Session d entrainement intensive pour le roster CDL'],
            ['title' => 'Reunion Staff', 'type' => 'meeting', 'date' => '2026-05-31', 'time' => '20:30', 'description' => 'Reunion hebdomadaire du staff NxR'],
            ['title' => 'Tournoi Summer Cup', 'type' => 'tournament', 'date' => '2026-06-05', 'time' => '14:00', 'description' => 'Participation au tournoi Summer Cup 2026'],
            ['title' => 'Match vs Team Vitality', 'type' => 'match', 'date' => '2026-06-08', 'time' => '18:00', 'description' => 'Match officiel CDL contre Team Vitality'],
        ];

        foreach ($eventsData as $data) {
            $event = new Event();

            $event
                ->setTitle($data['title'])
                ->setType($data['type'])
                ->setDate(new \DateTimeImmutable($data['date']))
                ->setTime($data['time'])
                ->setDescription($data['description']);

            $manager->persist($event);
        }

        $manager->flush();
    }
}
