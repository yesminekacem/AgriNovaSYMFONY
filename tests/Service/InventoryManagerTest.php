<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Inventory;
use App\Service\InventoryManager;
use PHPUnit\Framework\TestCase;

/**
 * Workshop Tests Unitaires — Couvre 2 règles métier sur Inventory :
 *  - Règle 1 : la quantité ne peut pas être négative
 *  - Règle 2 : le prix unitaire doit être strictement > 0
 *
 * Tests :
 *  - Cas valide
 *  - Règle 1 invalide (quantité négative)
 *  - Règle 2 invalide (prix unitaire = 0)
 *  - Test bonus : calcul de la valeur estimée
 */
final class InventoryManagerTest extends TestCase
{
    private InventoryManager $manager;

    protected function setUp(): void
    {
        $this->manager = new InventoryManager();
    }

    private function makeInventory(int $quantity, float $unitPrice): Inventory
    {
        $item = new Inventory();
        $item->setItemName('Tractor blade');
        $item->setItemType('TOOL');
        $item->setConditionStatus('GOOD');
        $item->setQuantity($quantity);
        $item->setUnitPrice($unitPrice);

        return $item;
    }

    public function testValidInventoryReturnsNoErrors(): void
    {
        $item = $this->makeInventory(10, 19.99);

        $errors = $this->manager->validateBusinessRules($item);

        self::assertSame([], $errors, 'Un item valide ne doit retourner aucune erreur.');
    }

    public function testNegativeQuantityIsRejected(): void
    {
        $item = $this->makeInventory(-3, 5.00);

        $errors = $this->manager->validateBusinessRules($item);

        self::assertContains(
            'Quantity cannot be negative.',
            $errors,
            'Une quantité négative doit déclencher la règle 1.'
        );
    }

    public function testZeroOrNegativeUnitPriceIsRejected(): void
    {
        $item = $this->makeInventory(5, 0.0);

        $errors = $this->manager->validateBusinessRules($item);

        self::assertContains(
            'Unit price must be greater than 0.',
            $errors,
            'Un prix unitaire <= 0 doit déclencher la règle 2.'
        );
    }

    public function testEstimatedValueIsCorrectlyComputed(): void
    {
        $item = $this->makeInventory(4, 12.50);

        self::assertSame(50.0, $this->manager->computeEstimatedValue($item));
    }

    public function testNeedsRestockBelowThreshold(): void
    {
        $item = $this->makeInventory(2, 5.00);

        self::assertTrue($this->manager->needsRestock($item, 5));
    }

    public function testNeedsRestockAboveThreshold(): void
    {
        $item = $this->makeInventory(20, 5.00);

        self::assertFalse($this->manager->needsRestock($item, 5));
     }
}
