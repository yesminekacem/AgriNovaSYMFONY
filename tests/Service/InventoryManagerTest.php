<?php

namespace App\Tests\Service;

use App\Entity\Inventory;
use App\Service\InventoryManager;
use PHPUnit\Framework\TestCase;

final class InventoryManagerTest extends TestCase
{
    private InventoryManager $manager;

    protected function setUp(): void
    {
        $this->manager = new InventoryManager();
    }

    public function testValidInventoryReturnsNoErrors(): void
    {
        $inventory = $this->makeInventory(
            quantity: 8,
            unitPrice: 14.5,
            isRentable: true,
            rentalPricePerDay: 4.0,
            lastMaintenanceDate: new \DateTimeImmutable('2026-05-01'),
            nextMaintenanceDate: new \DateTimeImmutable('2026-05-10'),
        );

        self::assertSame([], $this->manager->validateBusinessRules($inventory));
    }

    public function testQuantityMustBeGreaterThanZero(): void
    {
        $inventory = $this->makeInventory(quantity: 0, unitPrice: 10.0);

        self::assertContains('Quantity must be greater than 0.', $this->manager->validateBusinessRules($inventory));
    }

    public function testUnitPriceCannotBeNegative(): void
    {
        $inventory = $this->makeInventory(quantity: 4, unitPrice: -2.0);

        self::assertContains('Unit price cannot be negative.', $this->manager->validateBusinessRules($inventory));
    }

    public function testRentableItemsRequireRentalPrice(): void
    {
        $inventory = $this->makeInventory(quantity: 4, unitPrice: 10.0, isRentable: true, rentalPricePerDay: 0.0);

        self::assertContains('Rentable items need a rental price greater than 0.', $this->manager->validateBusinessRules($inventory));
    }

    public function testMaintenanceDatesMustStayChronological(): voidt
    {
        $inventory = $this->makeInventory(
            quantity: 4,
            unitPrice: 10.0,
            lastMaintenanceDate: new \DateTimeImmutable('2026-05-10'),
            nextMaintenanceDate: new \DateTimeImmutable('2026-05-01'),
        );

        self::assertContains('Next maintenance date must be after the last maintenance date.', $this->manager->validateBusinessRules($inventory));
    }

    public function testEstimatedValueIsComputed(): void
    {
        $inventory = $this->makeInventory(quantity: 3, unitPrice: 19.99);

        self::assertSame(59.97, $this->manager->computeEstimatedValue($inventory));
    }

    public function testNeedsRestockUsesThreshold(): void
    {
        $inventory = $this->makeInventory(quantity: 3, unitPrice: 19.99);

        self::assertTrue($this->manager->needsRestock($inventory, 5));
        self::assertFalse($this->manager->needsRestock($inventory, 2));
    }

    private function makeInventory(
        int $quantity,
        float $unitPrice,
        bool $isRentable = false,
        float $rentalPricePerDay = 0.0,
        ?\DateTimeInterface $lastMaintenanceDate = null,
        ?\DateTimeInterface $nextMaintenanceDate = null,
    ): Inventory {
        $inventory = new Inventory();
        $inventory->setQuantity($quantity);
        $inventory->setUnitPrice($unitPrice);
        $inventory->setIsRentable($isRentable);
        $inventory->setRentalPricePerDay($rentalPricePerDay);
        $inventory->setLastMaintenanceDate($lastMaintenanceDate);
        $inventory->setNextMaintenanceDate($nextMaintenanceDate);

        return $inventory;
    }
}
