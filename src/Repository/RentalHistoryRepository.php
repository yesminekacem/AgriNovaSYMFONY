<?php

namespace App\Repository;

use App\Entity\Rental;
use App\Entity\RentalHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RentalHistory>
 */
class RentalHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RentalHistory::class);
    }

    public function findForRental(Rental $rental): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.rental = :rental')
            ->setParameter('rental', $rental)
            ->orderBy('h.actionTimestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(string $search = '', ?string $actionType = null): array
    {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.rental', 'r')
            ->leftJoin('r.inventory', 'i')
            ->addSelect('r', 'i');

        if ($search !== '') {
            $qb
                ->andWhere('LOWER(COALESCE(h.performedBy, \'\')) LIKE LOWER(:search) OR LOWER(COALESCE(h.actionDescription, \'\')) LIKE LOWER(:search) OR LOWER(COALESCE(r.renterName, \'\')) LIKE LOWER(:search) OR LOWER(COALESCE(i.itemName, \'\')) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($actionType) {
            $qb->andWhere('h.actionType = :actionType')->setParameter('actionType', $actionType);
        }

        return $qb
            ->orderBy('h.actionTimestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAllHistory(): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countToday(): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.actionTimestamp >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByActionType(string $actionType): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.actionType = :actionType')
            ->setParameter('actionType', $actionType)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
