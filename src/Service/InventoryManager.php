<?php

namespace App\Service;

use App\Entity\Inventory;

final class InventoryManager
{
    /**
     * @return list<string>
     */
    public function validateBusinessRules(Inventory $inventory): array
    {
        $errors = [];

        if (($inventory->getQuantity() ?? 0) <= 0) {
            $errors[] = 'Quantity must be greater than 0.';
        }

        if (($inventory->getUnitPrice() ?? 0.0) < 0.0) {
            $errors[] = 'Unit price cannot be negative.';
        }

        if ($inventory->isRentable() && (($inventory->getRentalPricePerDay() ?? 0.0) <= 0.0)) {
            $errors[] = 'Rentable items need a rental price greater than 0.';
        }

        $lastMaintenanceDate = $inventory->getLastMaintenanceDate();
        $nextMaintenanceDate = $inventory->getNextMaintenanceDate();

        if ($lastMaintenanceDate !== null && $nextMaintenanceDate !== null && $nextMaintenanceDate < $lastMaintenanceDate) {
            $errors[] = 'Next maintenance date must be after the last maintenance date.';
        }

        return $errors;
    }

    public function computeEstimatedValue(Inventory $inventory): float
    {
        return round((float) ($inventory->getQuantity() ?? 0) * (float) ($inventory->getUnitPrice() ?? 0.0), 2);
    }

    public function needsRestock(Inventory $inventory, int $threshold = 5): bool
    {
        return ($inventory->getQuantity() ?? 0) <= $threshold;
    }
}
