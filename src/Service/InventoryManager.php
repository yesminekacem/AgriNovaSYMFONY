<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Inventory;

/**
 * Service métier pour gérer les règles applicatives sur l'entité Inventory.
 *
 * Workshop Tests Unitaires:
 *  - Règle 1 : la quantité ne peut pas être négative
 *  - Règle 2 : le prix unitaire doit être strictement > 0
 *  - Règle 3 (bonus) : la valeur estimée = quantité * prix unitaire
 */
final class InventoryManager
{
    /**
     * Valide les règles métier d'un item d'inventaire.
     *
     * @return list<string> liste des erreurs (vide si valide)
     */
    public function validateBusinessRules(Inventory $item): array
    {
        $errors = [];

        if (($item->getQuantity() ?? 0) < 0) {
            $errors[] = 'Quantity cannot be negative.';
        }

        if (($item->getUnitPrice() ?? 0.0) <= 0.0) {
            $errors[] = 'Unit price must be greater than 0.';
        }

        return $errors;
    }

    /**
     * Calcule la valeur totale d'un item (quantité * prix unitaire).
     */
    public function computeEstimatedValue(Inventory $item): float
    {
        $quantity = (float) ($item->getQuantity() ?? 0);
        $unitPrice = (float) ($item->getUnitPrice() ?? 0.0);

        return round($quantity * $unitPrice, 2);
    }

    /**
     * Indique si un item doit être réapprovisionné (stock <= seuil).
     */
    public function needsRestock(Inventory $item, int $threshold = 5): bool
    {
        return ($item->getQuantity() ?? 0) <= $threshold;
    }
}
