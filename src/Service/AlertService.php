<?php

namespace App\Service;

use App\Repository\InventoryRepository;
use App\Repository\RentalRepository;

class AlertService
{
    private InventoryRepository $inventoryRepository;
    private RentalRepository $rentalRepository;

    public function __construct(
        InventoryRepository $inventoryRepository,
        RentalRepository $rentalRepository
    ) {
        $this->inventoryRepository = $inventoryRepository;
        $this->rentalRepository = $rentalRepository;
    }

    public function getAlerts(): array
    {
        $alerts = [];

        // Overdue rentals
        $overdueRentals = $this->rentalRepository->findOverdue();
        foreach ($overdueRentals as $rental) {
            $alerts[] = [
                'type' => 'overdue_rental',
                'severity' => 'danger',
                'title' => 'Overdue Rental',
                'message' => sprintf(
                    'Rental #%d (%s) is %d days overdue',
                    $rental->getId(),
                    $rental->getDisplayItemName(),
                    abs($rental->getDaysRemaining())
                ),
                'link' => 'rental_show',
                'linkParams' => ['id' => $rental->getId()],
                'date' => $rental->getEndDate(),
            ];
        }

        // Upcoming returns (next 3 days)
        $upcomingReturns = $this->rentalRepository->findUpcomingReturns(3);
        foreach ($upcomingReturns as $rental) {
            $daysLeft = $rental->getDaysRemaining();
            $alerts[] = [
                'type' => 'upcoming_return',
                'severity' => $daysLeft <= 1 ? 'warning' : 'info',
                'title' => 'Return Due Soon',
                'message' => sprintf(
                    'Rental #%d (%s) due in %d day(s)',
                    $rental->getId(),
                    $rental->getDisplayItemName(),
                    $daysLeft
                ),
                'link' => 'rental_show',
                'linkParams' => ['id' => $rental->getId()],
                'date' => $rental->getEndDate(),
            ];
        }

        // Maintenance due (next 7 days)
        $maintenanceItems = $this->inventoryRepository->findNeedingMaintenance(7);
        foreach ($maintenanceItems as $item) {
            $alerts[] = [
                'type' => 'maintenance_due',
                'severity' => 'warning',
                'title' => 'Maintenance Due',
                'message' => sprintf(
                    '%s requires maintenance by %s',
                    $item->getItemName(),
                    $item->getNextMaintenanceDate()?->format('M d, Y') ?? ''
                ),
                'link' => 'inventory_show',
                'linkParams' => ['id' => $item->getId()],
                'date' => $item->getNextMaintenanceDate(),
            ];
        }

        // Low stock
        $lowStockItems = $this->inventoryRepository->findLowStock(5);
        foreach (array_slice($lowStockItems, 0, 5) as $item) {
            $alerts[] = [
                'type' => 'low_stock',
                'severity' => 'warning',
                'title' => 'Low Stock Alert',
                'message' => sprintf(
                    '%s only has %d unit(s) remaining',
                    $item->getItemName(),
                    $item->getQuantity()
                ),
                'link' => 'inventory_show',
                'linkParams' => ['id' => $item->getId()],
                'date' => new \DateTime(),
            ];
        }

        // Sort by severity (danger > warning > info)
        usort($alerts, static function (array $a, array $b): int {
            $severityOrder = ['danger' => 0, 'warning' => 1, 'info' => 2];
            return ($severityOrder[$a['severity']] ?? 2) - ($severityOrder[$b['severity']] ?? 2);
        });

        return $alerts;
    }

    public function getAlertCount(): int
    {
        return count($this->getAlerts());
    }

    public function getCriticalAlertCount(): int
    {
        return count(array_filter($this->getAlerts(), static fn(array $a): bool => $a['severity'] === 'danger'));
    }
}
