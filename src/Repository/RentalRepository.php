<?php

namespace App\Repository;

use App\Entity\Inventory;
use App\Entity\Rental;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rental>
 */
class RentalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rental::class);
    }

    public function findAllWithInventory(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.inventory', 'i')
            ->addSelect('i')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $term): array
    {
        return $this->findByFilters($term);
    }

    public function filterByStatus(string $status): array
    {
        return $this->findByFilters('', $status);
    }

    public function findByFilters(string $search = '', ?string $status = null, ?string $paymentStatus = null, bool $overdueOnly = false, ?int $inventoryId = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.inventory', 'i')
            ->addSelect('i');

        if ($search !== '') {
            $qb
                ->andWhere('LOWER(r.renterName) LIKE LOWER(:search) OR LOWER(r.ownerName) LIKE LOWER(:search) OR LOWER(r.renterContact) LIKE LOWER(:search) OR LOWER(COALESCE(i.itemName, \'\')) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($status) {
            $qb->andWhere('r.rentalStatus = :status')->setParameter('status', $status);
        }

        if ($paymentStatus) {
            $qb->andWhere('r.paymentStatus = :paymentStatus')->setParameter('paymentStatus', $paymentStatus);
        }

        if ($inventoryId !== null) {
            $qb->andWhere('i.id = :inventoryId')->setParameter('inventoryId', $inventoryId);
        }

        if ($overdueOnly) {
            $qb
                ->andWhere('r.actualReturnDate IS NULL')
                ->andWhere('r.endDate < :today')
                ->andWhere('r.rentalStatus IN (:openStatuses)')
                ->setParameter('today', new \DateTime('today'))
                ->setParameter('openStatuses', ['APPROVED', 'ACTIVE']);
        }

        return $qb
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdue(): array
    {
        return $this->findByFilters('', null, null, true);
    }

    public function findUpcomingReturns(int $days = 7): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.inventory', 'i')
            ->addSelect('i')
            ->andWhere('r.rentalStatus IN (:statuses)')
            ->andWhere('r.actualReturnDate IS NULL')
            ->andWhere('r.endDate BETWEEN :today AND :deadline')
            ->setParameter('statuses', ['APPROVED', 'ACTIVE'])
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('deadline', new \DateTime(sprintf('+%d days', $days)))
            ->orderBy('r.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.rentalStatus = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAllRentals(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOverdue(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.actualReturnDate IS NULL')
            ->andWhere('r.endDate < :today')
            ->andWhere('r.rentalStatus IN (:statuses)')
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('statuses', ['APPROVED', 'ACTIVE'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalRevenue(): float
    {
        return (float) ($this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.totalCost), 0)')
            ->andWhere('r.rentalStatus IN (:statuses)')
            ->setParameter('statuses', ['RETURNED', 'COMPLETED'])
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }

    public function countForInventory(Inventory $inventory): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.inventory = :inventory')
            ->setParameter('inventory', $inventory)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasDuplicateDraftForForm(
        Inventory $inventory,
        string $renterName,
        string $renterContact,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?int $excludeRentalId = null
    ): bool {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.inventory = :inventory')
            ->andWhere('LOWER(r.renterName) = LOWER(:renterName)')
            ->andWhere('LOWER(r.renterContact) = LOWER(:renterContact)')
            ->andWhere('r.startDate <= :endDate')
            ->andWhere('r.endDate >= :startDate')
            ->andWhere('r.rentalStatus NOT IN (:ignoredStatuses)')
            ->setParameter('inventory', $inventory)
            ->setParameter('renterName', trim($renterName))
            ->setParameter('renterContact', trim($renterContact))
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('ignoredStatuses', ['CANCELLED']);

        if ($excludeRentalId !== null) {
            $qb->andWhere('r.id != :excludeRentalId')->setParameter('excludeRentalId', $excludeRentalId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function hasBlockingRentalForInventory(Inventory $inventory, ?int $excludeRentalId = null): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.inventory = :inventory')
            ->andWhere('r.rentalStatus IN (:statuses)')
            ->setParameter('inventory', $inventory)
            ->setParameter('statuses', ['APPROVED', 'ACTIVE']);

        if ($excludeRentalId !== null) {
            $qb->andWhere('r.id != :excludeId')->setParameter('excludeId', $excludeRentalId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
