<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findActivePosts(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status OR p.status IS NULL')
            ->setParameter('status', 'ACTIVE')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
public function countPostsByCategory(): array
{
    return $this->createQueryBuilder('p')
        ->select('p.category AS name, COUNT(p.idPost) AS count')
        ->where('p.category IS NOT NULL')
        ->andWhere('p.category != :empty')
        ->setParameter('empty', '')
        ->groupBy('p.category')
        ->orderBy('count', 'DESC')
        ->getQuery()
        ->getArrayResult();
}
}