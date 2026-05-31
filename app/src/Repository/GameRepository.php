<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Season;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 *
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function findBySeason(Season $season): array
    {
        return $this->findBy(['season' => $season], ['playedAt' => 'DESC']);
    }

    public function findTeamGames(Team $team): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.teamA = :team OR g.teamB = :team')
            ->setParameter('team', $team)
            ->orderBy('g.playedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTeamGamesBySeason(Team $team, Season $season): array
    {
        return $this->createQueryBuilder('g')
            ->where('(g.teamA = :team OR g.teamB = :team)')
            ->andWhere('g.season = :season')
            ->setParameter('team', $team)
            ->setParameter('season', $season)
            ->orderBy('g.playedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTeamWins(Team $team): array
    {
        return $this->findBy(['winnerTeam' => $team], ['playedAt' => 'DESC']);
    }

    public function findRecentGames(int $limit = 10): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.playedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
