<?php

namespace App\Entity;

use App\Repository\InventoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: InventoryRepository::class)]
#[ORM\Table(name: 'inventory')]
#[ORM\HasLifecycleCallbacks]
class Inventory
{
    public const ITEM_TYPES = ['EQUIPMENT', 'TOOL', 'CONSUMABLE', 'STORAGE'];
    public const CONDITION_STATUSES = ['EXCELLENT', 'GOOD', 'FAIR', 'POOR'];
    public const RENTAL_STATUSES = ['AVAILABLE', 'RENTED_OUT', 'IN_USE', 'MAINTENANCE', 'RETIRED'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'inventory_id')]
    private ?int $id = null;

    #[ORM\Column(name: 'item_name', length: 255)]
    #[Assert\NotBlank(message: 'Item name is required.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Item name must be at least {{ limit }} characters.')]
    private ?string $itemName = null;

    #[ORM\Column(name: 'item_type', length: 20)]
    #[Assert\NotBlank(message: 'Item type is required.')]
    #[Assert\Choice(choices: self::ITEM_TYPES, message: 'Choose a valid item type.')]
    private ?string $itemType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000, maxMessage: 'Description is too long.')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Quantity is required.')]
    #[Assert\Positive(message: 'Quantity must be greater than 0.')]
    private ?int $quantity = 1;

    #[ORM\Column(name: 'unit_price')]
    #[Assert\NotNull(message: 'Unit price is required.')]
    #[Assert\PositiveOrZero(message: 'Unit price cannot be negative.')]
    private ?float $unitPrice = 0.0;

    #[ORM\Column(name: 'purchase_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $purchaseDate = null;

    #[ORM\Column(name: 'condition_status', length: 20)]
    #[Assert\NotBlank(message: 'Condition status is required.')]
    #[Assert\Choice(choices: self::CONDITION_STATUSES, message: 'Choose a valid condition status.')]
    private ?string $conditionStatus = 'GOOD';

    #[ORM\Column(name: 'is_rentable')]
    private bool $isRentable = false;

    #[ORM\Column(name: 'rental_price_per_day', nullable: true)]
    #[Assert\PositiveOrZero(message: 'Rental price per day cannot be negative.')]
    private ?float $rentalPricePerDay = 0.0;

    #[ORM\Column(name: 'rental_status', length: 20)]
    #[Assert\Choice(choices: self::RENTAL_STATUSES, message: 'Choose a valid rental status.')]
    private ?string $rentalStatus = 'AVAILABLE';

    #[ORM\Column(name: 'last_maintenance_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastMaintenanceDate = null;

    #[ORM\Column(name: 'next_maintenance_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextMaintenanceDate = null;

    #[ORM\Column(name: 'total_usage_hours', nullable: true)]
    #[Assert\PositiveOrZero(message: 'Total usage hours cannot be negative.')]
    private ?int $totalUsageHours = 0;

    #[ORM\Column(name: 'owner_name', length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Owner name is too long.')]
    private ?string $ownerName = null;

    #[ORM\Column(name: 'owner_contact', length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Owner contact is too long.')]
    private ?string $ownerContact = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'image_path', length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'Image path is too long.')]
    private ?string $imagePath = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItemName(): ?string
    {
        return $this->itemName;
    }

    public function setItemName(?string $itemName): static
    {
        $this->itemName = $itemName !== null ? trim($itemName) : null;

        return $this;
    }

    public function getItemType(): ?string
    {
        return $this->itemType;
    }

    public function setItemType(?string $itemType): static
    {
        $this->itemType = $itemType;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description !== null ? trim($description) : null;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?float $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTimeInterface $purchaseDate): static
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }

    public function getConditionStatus(): ?string
    {
        return $this->conditionStatus;
    }

    public function setConditionStatus(?string $conditionStatus): static
    {
        $this->conditionStatus = $conditionStatus;

        return $this;
    }

    public function isRentable(): bool
    {
        return $this->isRentable;
    }

    public function setIsRentable(bool $isRentable): static
    {
        $this->isRentable = $isRentable;

        return $this;
    }

    public function getRentalPricePerDay(): ?float
    {
        return $this->rentalPricePerDay;
    }

    public function setRentalPricePerDay(?float $rentalPricePerDay): static
    {
        $this->rentalPricePerDay = $rentalPricePerDay;

        return $this;
    }

    public function getRentalStatus(): ?string
    {
        return $this->rentalStatus;
    }

    public function setRentalStatus(?string $rentalStatus): static
    {
        $this->rentalStatus = $rentalStatus;

        return $this;
    }

    public function getLastMaintenanceDate(): ?\DateTimeInterface
    {
        return $this->lastMaintenanceDate;
    }

    public function setLastMaintenanceDate(?\DateTimeInterface $lastMaintenanceDate): static
    {
        $this->lastMaintenanceDate = $lastMaintenanceDate;

        return $this;
    }

    public function getNextMaintenanceDate(): ?\DateTimeInterface
    {
        return $this->nextMaintenanceDate;
    }

    public function setNextMaintenanceDate(?\DateTimeInterface $nextMaintenanceDate): static
    {
        $this->nextMaintenanceDate = $nextMaintenanceDate;

        return $this;
    }

    public function getTotalUsageHours(): ?int
    {
        return $this->totalUsageHours;
    }

    public function setTotalUsageHours(?int $totalUsageHours): static
    {
        $this->totalUsageHours = $totalUsageHours;

        return $this;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function setOwnerName(?string $ownerName): static
    {
        $this->ownerName = $ownerName !== null ? trim($ownerName) : null;

        return $this;
    }

    public function getOwnerContact(): ?string
    {
        return $this->ownerContact;
    }

    public function setOwnerContact(?string $ownerContact): static
    {
        $this->ownerContact = $ownerContact !== null ? trim($ownerContact) : null;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath !== null ? trim($imagePath) : null;

        return $this;
    }

    public function __toString(): string
    {
        return $this->itemName ?? 'Inventory item';
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->isRentable && (($this->rentalPricePerDay ?? 0) <= 0)) {
            $context->buildViolation('Rentable items need a rental price greater than 0.')
                ->atPath('rentalPricePerDay')
                ->addViolation();
        }

        if (!$this->isRentable && ($this->rentalPricePerDay ?? 0) > 0) {
            $context->buildViolation('Enable rentable first if you want to set a rental price.')
                ->atPath('isRentable')
                ->addViolation();
        }

        if ($this->lastMaintenanceDate && $this->nextMaintenanceDate && $this->nextMaintenanceDate < $this->lastMaintenanceDate) {
            $context->buildViolation('Next maintenance date must be after the last maintenance date.')
                ->atPath('nextMaintenanceDate')
                ->addViolation();
        }

        if ($this->purchaseDate && $this->purchaseDate > new \DateTime('today')) {
            $context->buildViolation('Purchase date cannot be in the future.')
                ->atPath('purchaseDate')
                ->addViolation();
        }
    }

    public function needsMaintenanceSoon(int $days = 7): bool
    {
        if (!$this->nextMaintenanceDate) {
            return false;
        }

        return $this->nextMaintenanceDate <= new \DateTime(sprintf('+%d days', $days));
    }

    public function isLowStock(int $threshold = 5): bool
    {
        return ($this->quantity ?? 0) <= $threshold;
    }

    public function getEstimatedValue(): float
    {
        return (float) ($this->quantity ?? 0) * (float) ($this->unitPrice ?? 0);
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTime();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
