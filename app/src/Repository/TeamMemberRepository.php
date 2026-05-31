<?php

namespace App\Repository;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamMember>
 *
 * @method TeamMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method TeamMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method TeamMember[]    findAll()
 * @method TeamMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamMember::class);
    }

    public function findActiveMembers(Team $team): array
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.team = :team')
            ->andWhere('tm.leftAt IS NULL')
            ->setParameter('team', $team)
            ->orderBy('tm.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUserTeams(User $user): array
    {
        return $this->createQueryBuilder('tm')
            ->select('tm.team')
            ->where('tm.user = :user')
            ->andWhere('tm.leftAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findUserCurrentTeam(User $user): ?TeamMember
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.user = :user')
            ->andWhere('tm.leftAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('tm.joinedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
