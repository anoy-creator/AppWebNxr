<?php

namespace App\Repository;

use App\Entity\GameMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMap>
 *
 * @method GameMap|null find($id, $lockMode = null, $lockVersion = null)
 * @method GameMap|null findOneBy(array $criteria, array $orderBy = null)
 * @method GameMap[]    findAll()
 * @method GameMap[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameMapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMap::class);
    }

    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['name' => 'ASC']);
    }

    public function findByMode(string $mode): array
    {
        return $this->findBy(['mode' => $mode], ['name' => 'ASC']);
    }

    public function findByNameAndMode(string $name, string $mode): ?GameMap
    {
        return $this->findOneBy(['name' => $name, 'mode' => $mode]);
    }
}
