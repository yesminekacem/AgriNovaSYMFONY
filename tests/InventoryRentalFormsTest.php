<?php

namespace App\Tests;

use App\Controller\RentalController;
use App\Entity\Inventory;
use App\Entity\Rental;
use App\Form\InventoryType;
use App\Form\RentalType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class InventoryRentalFormsTest extends TestCase
{
    public function testInventoryCreateFormForUserHidesInternalFields(): void
    {
        $fields = $this->buildFormFields(new InventoryType(), [
            'is_admin' => false,
            'is_edit' => false,
        ]);

        self::assertArrayHasKey('itemName', $fields);
        self::assertArrayHasKey('isRentable', $fields);
        self::assertArrayNotHasKey('ownerName', $fields);
        self::assertArrayNotHasKey('ownerContact', $fields);
        self::assertArrayNotHasKey('rentalStatus', $fields);
    }

    public function testInventoryEditFormForAdminShowsOperationalFields(): void
    {
        $fields = $this->buildFormFields(new InventoryType(), [
            'is_admin' => true,
            'is_edit' => true,
        ]);

        self::assertArrayHasKey('ownerName', $fields);
        self::assertArrayHasKey('ownerContact', $fields);
        self::assertArrayHasKey('rentalStatus', $fields);
    }

    public function testRentalCreateFormForUserKeepsOnlyRequestFields(): void
    {
        $fields = $this->buildFormFields(new RentalType(), [
            'inventory_id' => 1,
            'current_rental_id' => null,
            'lock_inventory' => true,
            'lock_renter' => false,
            'is_admin' => false,
            'is_edit' => false,
        ]);

        self::assertArrayHasKey('inventory', $fields);
        self::assertArrayHasKey('paymentMethod', $fields);
        self::assertTrue($fields['dailyRate']['disabled']);
        self::assertArrayNotHasKey('paymentStatus', $fields);
        self::assertArrayNotHasKey('deliveryFee', $fields);
        self::assertArrayNotHasKey('actualReturnDate', $fields);
        self::assertArrayNotHasKey('pickupCondition', $fields);
        self::assertArrayNotHasKey('returnCondition', $fields);
        self::assertArrayNotHasKey('pickupPhotos', $fields);
        self::assertArrayNotHasKey('returnPhotos', $fields);
        self::assertArrayNotHasKey('damageNotes', $fields);
        self::assertArrayNotHasKey('ownerRating', $fields);
        self::assertArrayNotHasKey('renterRating', $fields);
        self::assertArrayNotHasKey('ownerReview', $fields);
        self::assertArrayNotHasKey('renterReview', $fields);
        self::assertArrayNotHasKey('rentalStatus', $fields);
    }

    public function testRentalEditFormForAdminShowsOperationalFields(): void
    {
        $fields = $this->buildFormFields(new RentalType(), [
            'inventory_id' => 1,
            'current_rental_id' => 2,
            'lock_inventory' => true,
            'lock_renter' => false,
            'is_admin' => true,
            'is_edit' => true,
        ]);

        self::assertFalse($fields['dailyRate']['disabled']);
        self::assertArrayHasKey('deliveryFee', $fields);
        self::assertArrayHasKey('paymentStatus', $fields);
        self::assertArrayHasKey('actualReturnDate', $fields);
        self::assertArrayHasKey('pickupCondition', $fields);
        self::assertArrayHasKey('returnCondition', $fields);
        self::assertArrayHasKey('pickupPhotos', $fields);
        self::assertArrayHasKey('returnPhotos', $fields);
        self::assertArrayHasKey('damageNotes', $fields);
    }

    public function testInventoryValidationRequiresRentalPriceForRentableItem(): void
    {
        $inventory = (new Inventory())
            ->setItemName('Seeder')
            ->setItemType('EQUIPMENT')
            ->setConditionStatus('GOOD')
            ->setQuantity(1)
            ->setUnitPrice(5000.0)
            ->setIsRentable(true)
            ->setRentalPricePerDay(0.0);

        $this->expectViolation(
            $this->createMock(ExecutionContextInterface::class),
            'Rentable items need a rental price greater than 0.',
            'rentalPricePerDay'
        );

        $inventory->validate($this->context);
    }

    public function testRentalValidationRequiresDeliveryAddressWhenDeliveryIsEnabled(): void
    {
        $rental = (new Rental())
            ->setInventory($this->buildInventory())
            ->setOwnerName('Agri Owner')
            ->setRenterName('Test User')
            ->setRenterContact('test@example.com')
            ->setStartDate(new \DateTimeImmutable('2026-04-21'))
            ->setEndDate(new \DateTimeImmutable('2026-04-23'))
            ->setDailyRate(90.0)
            ->setRequiresDelivery(true)
            ->setDeliveryAddress(null);

        $this->expectViolation(
            $this->createMock(ExecutionContextInterface::class),
            'Delivery address is required when delivery is enabled.',
            'deliveryAddress'
        );

        $rental->validate($this->context);
    }

    public function testRentalWorkflowAllowsApprovedToActiveTransition(): void
    {
        $reflection = new \ReflectionClass(RentalController::class);
        $method = $reflection->getMethod('getAllowedTransitions');
        $method->setAccessible(true);

        /** @var array<string, array<int, string>> $transitions */
        $transitions = $method->invoke(new RentalController());

        self::assertArrayHasKey('ACTIVE', $transitions);
        self::assertSame(['APPROVED'], $transitions['ACTIVE']);
        self::assertArrayNotHasKey('ACTIVATED', $transitions);
    }

    private ExecutionContextInterface $context;

    /**
     * @param array<string, mixed> $options
     * @return array<string, array<string, mixed>>
     */
    private function buildFormFields(object $type, array $options): array
    {
        $fields = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(
            function (string $name, ?string $fieldType = null, array $fieldOptions = []) use (&$fields, $builder) {
                $fields[$name] = $fieldOptions;

                return $builder;
            }
        );

        $type->buildForm($builder, $options);

        return $fields;
    }

    private function buildInventory(): Inventory
    {
        return (new Inventory())
            ->setItemName('Seeder')
            ->setItemType('EQUIPMENT')
            ->setConditionStatus('GOOD')
            ->setQuantity(1)
            ->setUnitPrice(5000.0)
            ->setIsRentable(true)
            ->setRentalStatus('AVAILABLE')
            ->setRentalPricePerDay(120.0);
    }

    private function expectViolation(ExecutionContextInterface $context, string $message, string $path): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())
            ->method('atPath')
            ->with($path)
            ->willReturnSelf();
        $builder->expects(self::once())
            ->method('addViolation');

        $context->expects(self::once())
            ->method('buildViolation')
            ->with($message)
            ->willReturn($builder);

        $this->context = $context;
    }
}
