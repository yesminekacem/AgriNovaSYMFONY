<?php

namespace App\Entity;

use App\Repository\InventoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryRepository::class)]
#[ORM\Table(name: 'inventory')]
class Inventory
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $inventoryId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $itemName;

    #[ORM\Column(type: 'string')]
    private string $itemType;

    #[ORM\Column(type: 'text', nullable: true, length: 65535)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'float', precision: 22)]
    private float $unitPrice;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $purchaseDate = null;

    #[ORM\Column(type: 'string')]
    private string $conditionStatus;

    #[ORM\Column(type: 'boolean')]
    private bool $isRentable;

    #[ORM\Column(type: 'float', nullable: true, precision: 22)]
    private ?float $rentalPricePerDay = null;

    #[ORM\Column(type: 'string')]
    private string $rentalStatus;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $lastMaintenanceDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $nextMaintenanceDate = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $totalUsageHours = null;

    #[ORM\Column(type: 'string', nullable: true, length: 255)]
    private ?string $ownerName = null;

    #[ORM\Column(type: 'string', nullable: true, length: 100)]
    private ?string $ownerContact = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'string', nullable: true, length: 500)]
    private ?string $imagePath = null;


    public function getInventoryId(): ?int
    {
        return $this->inventoryId;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function setItemName(string $itemName): self
    {
        $this->itemName = $itemName;
        return $this;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): self
    {
        $this->itemType = $itemType;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTimeInterface $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;
        return $this;
    }

    public function getConditionStatus(): string
    {
        return $this->conditionStatus;
    }

    public function setConditionStatus(string $conditionStatus): self
    {
        $this->conditionStatus = $conditionStatus;
        return $this;
    }

    public function getIsRentable(): bool
    {
        return $this->isRentable;
    }

    public function setIsRentable(bool $isRentable): self
    {
        $this->isRentable = $isRentable;
        return $this;
    }

    public function getRentalPricePerDay(): ?float
    {
        return $this->rentalPricePerDay;
    }

    public function setRentalPricePerDay(?float $rentalPricePerDay): self
    {
        $this->rentalPricePerDay = $rentalPricePerDay;
        return $this;
    }

    public function getRentalStatus(): string
    {
        return $this->rentalStatus;
    }

    public function setRentalStatus(string $rentalStatus): self
    {
        $this->rentalStatus = $rentalStatus;
        return $this;
    }

    public function getLastMaintenanceDate(): ?\DateTimeInterface
    {
        return $this->lastMaintenanceDate;
    }

    public function setLastMaintenanceDate(?\DateTimeInterface $lastMaintenanceDate): self
    {
        $this->lastMaintenanceDate = $lastMaintenanceDate;
        return $this;
    }

    public function getNextMaintenanceDate(): ?\DateTimeInterface
    {
        return $this->nextMaintenanceDate;
    }

    public function setNextMaintenanceDate(?\DateTimeInterface $nextMaintenanceDate): self
    {
        $this->nextMaintenanceDate = $nextMaintenanceDate;
        return $this;
    }

    public function getTotalUsageHours(): ?int
    {
        return $this->totalUsageHours;
    }

    public function setTotalUsageHours(?int $totalUsageHours): self
    {
        $this->totalUsageHours = $totalUsageHours;
        return $this;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function setOwnerName(?string $ownerName): self
    {
        $this->ownerName = $ownerName;
        return $this;
    }

    public function getOwnerContact(): ?string
    {
        return $this->ownerContact;
    }

    public function setOwnerContact(?string $ownerContact): self
    {
        $this->ownerContact = $ownerContact;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;
        return $this;
    }

}
