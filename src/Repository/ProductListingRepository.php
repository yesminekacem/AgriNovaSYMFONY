<?php

namespace App\Repository;

use App\Entity\ProductListing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductListing::class);
    }

    /**
     * @return ProductListing[] Returns an array of ProductListing objects
     */
    public function searchMarketplace(?string $query, ?string $excludeUserId, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($excludeUserId) {
            $qb->andWhere('p.userId != :userId')
               ->setParameter('userId', $excludeUserId);
        }

        if ($query) {
            $qb->andWhere('p.productName LIKE :query OR p.description LIKE :query OR p.category LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($category) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }
}
