<?php

namespace App\Service;

final class SiteDataProvider
{
    public function getData(): array
    {
        return [
            'stats' => [
                'members' => 47,
                'matchesPlayed' => 156,
                'tournamentsWon' => 12,
                'winrate' => '72.4%',
            ],
            'players' => [
                ['pseudo' => 'ShadowX', 'avatar' => 'https://images.unsplash.com/photo-1511367461989-f85a21fda167?w=700', 'role' => 'Joueur', 'grade' => 'Captain', 'game' => 'Call of Duty', 'socials' => ['twitter' => 'shadowx', 'twitch' => 'shadowx_nxr']],
                ['pseudo' => 'NeonKnight', 'avatar' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=700', 'role' => 'Joueur', 'grade' => 'Pro Player', 'game' => 'Warzone', 'socials' => ['twitch' => 'neonknight', 'youtube' => 'neonknight']],
                ['pseudo' => 'VortexPro', 'avatar' => 'https://images.unsplash.com/photo-1509967419530-da38b4704bc6?w=700', 'role' => 'Streamer', 'grade' => 'Content Creator', 'game' => 'Multi-Gaming', 'socials' => ['twitter' => 'vortexpro', 'twitch' => 'vortexpro', 'youtube' => 'vortex']],
                ['pseudo' => 'QuantumAce', 'avatar' => 'https://images.unsplash.com/photo-1560253023-3ec5d502959f?w=700', 'role' => 'Joueur', 'grade' => 'Pro Player', 'game' => 'Call of Duty', 'socials' => ['twitter' => 'quantumace']],
                ['pseudo' => 'BlazeFury', 'avatar' => 'https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=700', 'role' => 'Staff', 'grade' => 'Coach', 'game' => 'All Games', 'socials' => ['twitter' => 'blazefury']],
                ['pseudo' => 'CyberStorm', 'avatar' => 'https://images.unsplash.com/photo-1552374196-1ab2a1c593e8?w=700', 'role' => 'Createur', 'grade' => 'Video Editor', 'game' => 'Content', 'socials' => ['twitter' => 'cyberstorm', 'youtube' => 'cyberstorm']],
            ],
            'rosters' => [
                [
                    'name' => 'NxR Warzone',
                    'game' => 'Call of Duty: Warzone',
                    'banner' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1400',
                    'stats' => ['wins' => 47, 'losses' => 13, 'winrate' => '78.3%'],
                    'players' => [
                        ['pseudo' => 'ShadowX', 'role' => 'Captain'],
                        ['pseudo' => 'NeonKnight', 'role' => 'Player'],
                        ['pseudo' => 'QuantumAce', 'role' => 'Player'],
                        ['pseudo' => 'VortexPro', 'role' => 'Sub'],
                    ],
                ],
                [
                    'name' => 'NxR CDL',
                    'game' => 'Call of Duty League',
                    'banner' => 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=1400',
                    'stats' => ['wins' => 32, 'losses' => 18, 'winrate' => '64.0%'],
                    'players' => [
                        ['pseudo' => 'QuantumAce', 'role' => 'Captain'],
                        ['pseudo' => 'ShadowX', 'role' => 'Player'],
                        ['pseudo' => 'NeonKnight', 'role' => 'Player'],
                        ['pseudo' => 'VortexPro', 'role' => 'Player'],
                        ['pseudo' => 'BlazeFury', 'role' => 'Sub'],
                    ],
                ],
            ],
            'matches' => [
                ['date' => '2026-05-28', 'opponent' => 'Team Horizon', 'mode' => 'Battle Royale', 'result' => 'Victory', 'score' => '3-1', 'game' => 'Warzone'],
                ['date' => '2026-05-25', 'opponent' => 'Elite Squad', 'mode' => 'Hardpoint', 'result' => 'Victory', 'score' => '250-187', 'game' => 'CDL'],
                ['date' => '2026-05-22', 'opponent' => 'Phantom Gaming', 'mode' => 'Search & Destroy', 'result' => 'Defeat', 'score' => '4-6', 'game' => 'CDL'],
                ['date' => '2026-05-20', 'opponent' => 'Thunder Esports', 'mode' => 'Resurgence', 'result' => 'Victory', 'score' => '5-0', 'game' => 'Warzone'],
                ['date' => '2026-05-18', 'opponent' => 'Apex Legends', 'mode' => 'Tournament Finals', 'result' => 'Victory', 'score' => '3-2', 'game' => 'Tournament'],
            ],
            'news' => [
                ['title' => 'NxR remporte le championnat Warzone Spring 2026', 'author' => 'BlazeFury', 'date' => '2026-05-27', 'image' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=900', 'excerpt' => 'Une victoire ecrasante pour notre roster Warzone qui domine la competition.', 'content' => 'Notre equipe Warzone a brille lors du championnat Spring 2026 en remportant le titre avec une performance exceptionnelle.'],
                ['title' => 'Nouveau partenariat avec HyperX', 'author' => 'CyberStorm', 'date' => '2026-05-20', 'image' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=900', 'excerpt' => 'NxR annonce un partenariat strategique avec HyperX pour equiper tous nos joueurs.', 'content' => 'Nous sommes fiers d annoncer notre nouveau partenariat avec HyperX. Tous nos joueurs seront equipes du meilleur materiel gaming.'],
                ['title' => 'Recrutement: nous recherchons des talents', 'author' => 'BlazeFury', 'date' => '2026-05-15', 'image' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=900', 'excerpt' => 'NxR ouvre ses portes a de nouveaux talents pour renforcer ses rosters.', 'content' => 'Vous pensez avoir le niveau pour rejoindre NxR ? Nous recherchons activement de nouveaux joueurs talentueux pour nos equipes Warzone et CDL.'],
            ],
            'events' => [
                ['title' => 'Entrainement CDL', 'type' => 'training', 'date' => '2026-05-30', 'time' => '19:00', 'description' => 'Session d entrainement intensive pour le roster CDL'],
                ['title' => 'Reunion Staff', 'type' => 'meeting', 'date' => '2026-05-31', 'time' => '20:30', 'description' => 'Reunion hebdomadaire du staff NxR'],
                ['title' => 'Tournoi Summer Cup', 'type' => 'tournament', 'date' => '2026-06-05', 'time' => '14:00', 'description' => 'Participation au tournoi Summer Cup 2026'],
                ['title' => 'Match vs Team Vitality', 'type' => 'match', 'date' => '2026-06-08', 'time' => '18:00', 'description' => 'Match officiel CDL contre Team Vitality'],
            ],
        ];
    }
}
