<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * @return Recipe[]
     */
    public function searchByAuthor(\App\Entity\User $user, string $query): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.author = :user')
            ->andWhere('LOWER(r.title) LIKE :q')
            ->setParameter('user', $user)
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('r.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Recipe[]
     */
    public function findAllPublic(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isPublic = true')
            ->orderBy('r.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Recipe[]
     */
    public function searchPublic(string $query): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isPublic = true')
            ->andWhere('LOWER(r.title) LIKE :q')
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('r.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
