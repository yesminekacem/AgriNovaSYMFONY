<?php

namespace App\Repository;

use App\Entity\Inventory;
use App\Entity\Rental;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inventory>
 */
class InventoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inventory::class);
    }

    public function search(string $term): array
    {
        return $this->findByFilters($term);
    }

    public function filter(?string $type, ?string $condition, ?bool $rentable, ?string $status = null): array
    {
        return $this->findByFilters('', $type, $condition, $rentable, $status);
    }

    public function findByFilters(
        string $search = '',
        ?string $type = null,
        ?string $condition = null,
        ?bool $rentable = null,
        ?string $status = null
    ): array {
        $qb = $this->createQueryBuilder('i');

        if ($search !== '') {
            $qb
                ->andWhere('LOWER(i.itemName) LIKE LOWER(:search) OR LOWER(COALESCE(i.ownerName, \'\')) LIKE LOWER(:search) OR LOWER(COALESCE(i.ownerContact, \'\')) LIKE LOWER(:search) OR LOWER(i.itemType) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($type) {
            $qb->andWhere('i.itemType = :type')->setParameter('type', $type);
        }

        if ($condition) {
            $qb->andWhere('i.conditionStatus = :condition')->setParameter('condition', $condition);
        }

        if ($rentable !== null) {
            $qb->andWhere('i.isRentable = :rentable')->setParameter('rentable', $rentable);
        }

        if ($status) {
            $qb->andWhere('i.rentalStatus = :status')->setParameter('status', $status);
        }

        return $qb
            ->orderBy('i.updatedAt', 'DESC')
            ->addOrderBy('i.itemName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNeedingMaintenance(int $days = 7): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.nextMaintenanceDate IS NOT NULL')
            ->andWhere('i.nextMaintenanceDate <= :deadline')
            ->setParameter('deadline', new \DateTime(sprintf('+%d days', $days)))
            ->orderBy('i.nextMaintenanceDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLowStock(int $threshold = 5): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.quantity <= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('i.quantity', 'ASC')
            ->addOrderBy('i.itemName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByRentalStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.rentalStatus = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAllItems(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countRentableItems(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.isRentable = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countLowStock(int $threshold = 5): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.quantity <= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countMaintenanceDue(int $days = 7): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.nextMaintenanceDate IS NOT NULL')
            ->andWhere('i.nextMaintenanceDate <= :deadline')
            ->setParameter('deadline', new \DateTime(sprintf('+%d days', $days)))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalValue(): float
    {
        return (float) ($this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.unitPrice * i.quantity), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }

    public function getRentableValue(): float
    {
        return (float) ($this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.rentalPricePerDay), 0)')
            ->andWhere('i.isRentable = true')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }

    public function createRentableForRentalFormQueryBuilder(?int $selectedInventoryId = null, ?int $excludeRentalId = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.isRentable = true');

        $subQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(Rental::class, 'r')
            ->andWhere('r.inventory = i')
            ->andWhere('r.rentalStatus IN (:blockingStatuses)');

        if ($excludeRentalId !== null) {
            $subQuery
                ->andWhere('r.id != :excludeRentalId');
            $qb->setParameter('excludeRentalId', $excludeRentalId);
        }

        $availabilityExpression = sprintf(
            '(i.rentalStatus NOT IN (:inventoryBlockedStatuses) AND NOT EXISTS (%s))',
            $subQuery->getDQL()
        );

        if ($selectedInventoryId !== null) {
            $qb
                ->andWhere(sprintf('(i.id = :selectedInventoryId OR %s)', $availabilityExpression))
                ->setParameter('selectedInventoryId', $selectedInventoryId);
        } else {
            $qb->andWhere($availabilityExpression);
        }

        return $qb
            ->setParameter('inventoryBlockedStatuses', ['MAINTENANCE', 'RETIRED'])
            ->setParameter('blockingStatuses', ['APPROVED', 'ACTIVE'])
            ->orderBy('i.itemName', 'ASC');
    }

    public function findAvailableForRentalForm(int $inventoryId, ?int $excludeRentalId = null): ?Inventory
    {
        return $this->createRentableForRentalFormQueryBuilder($inventoryId, $excludeRentalId)
            ->andWhere('i.id = :inventoryId')
            ->setParameter('inventoryId', $inventoryId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createSelectedInventoryQueryBuilder(int $inventoryId): QueryBuilder
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id = :selectedInventoryId')
            ->setParameter('selectedInventoryId', $inventoryId);
    }
}
