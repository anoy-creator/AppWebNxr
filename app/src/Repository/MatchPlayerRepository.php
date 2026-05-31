<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\MatchPlayer;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MatchPlayer>
 *
 * @method MatchPlayer|null find($id, $lockMode = null, $lockVersion = null)
 * @method MatchPlayer|null findOneBy(array $criteria, array $orderBy = null)
 * @method MatchPlayer[]    findAll()
 * @method MatchPlayer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MatchPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MatchPlayer::class);
    }

    public function findByGame(Game $game): array
    {
        return $this->findBy(['game' => $game], ['score' => 'DESC']);
    }

    public function findPlayerGames(User $user): array
    {
        return $this->createQueryBuilder('mp')
            ->where('mp.user = :user')
            ->setParameter('user', $user)
            ->orderBy('mp.game.playedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPlayerStats(User $user): array
    {
        return $this->createQueryBuilder('mp')
            ->select(
                'SUM(mp.kills) as totalKills',
                'SUM(mp.deaths) as totalDeaths',
                'SUM(mp.assists) as totalAssists',
                'SUM(mp.score) as totalScore',
                'SUM(mp.damage) as totalDamage',
                'COUNT(mp.id) as gamesPlayed',
                'AVG(mp.kills) as avgKills',
                'AVG(mp.deaths) as avgDeaths',
                'AVG(mp.score) as avgScore'
            )
            ->where('mp.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findTeamPlayerStats(Team $team): array
    {
        return $this->createQueryBuilder('mp')
            ->select('mp.user, COUNT(mp.id) as gameCount, AVG(mp.score) as avgScore')
            ->where('mp.team = :team')
            ->setParameter('team', $team)
            ->groupBy('mp.user')
            ->orderBy('avgScore', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTopPlayers(int $limit = 10): array
    {
        return $this->createQueryBuilder('mp')
            ->select('mp.user, AVG(mp.score) as avgScore, COUNT(mp.id) as gameCount')
            ->groupBy('mp.user')
            ->orderBy('avgScore', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
