<?php

namespace App\Entity;

use App\Repository\RentalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: RentalRepository::class)]
#[ORM\Table(name: 'rental')]
#[ORM\HasLifecycleCallbacks]
class Rental
{
    public const RENTAL_STATUSES = ['PENDING', 'APPROVED', 'ACTIVE', 'RETURNED', 'COMPLETED', 'CANCELLED', 'DISPUTED'];
    public const PAYMENT_STATUSES = ['PENDING', 'DEPOSIT_PAID', 'FULLY_PAID', 'REFUNDED', 'DISPUTED'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'rental_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Inventory::class)]
    #[ORM\JoinColumn(name: 'inventory_id', referencedColumnName: 'inventory_id', nullable: false)]
    #[Assert\NotNull(message: 'Please select an inventory item.')]
    private ?Inventory $inventory = null;

    #[ORM\Column(name: 'owner_name', length: 255)]
    #[Assert\NotBlank(message: 'Owner name is required.')]
    private ?string $ownerName = null;

    #[ORM\Column(name: 'renter_name', length: 255)]
    #[Assert\NotBlank(message: 'Renter name is required.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Renter name must be at least {{ limit }} characters.')]
    private ?string $renterName = null;

    #[ORM\Column(name: 'renter_contact', length: 100)]
    #[Assert\NotBlank(message: 'Renter contact is required.')]
    #[Assert\Length(max: 100, maxMessage: 'Renter contact is too long.')]
    private ?string $renterContact = null;

    #[ORM\Column(name: 'renter_address', length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'Renter address is too long.')]
    private ?string $renterAddress = null;

    #[ORM\Column(name: 'start_date', type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'Start date is required.')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'End date is required.')]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: 'actual_return_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $actualReturnDate = null;

    // Workshop Doctor Doctrine: decimal column with explicit precision/scale
    #[ORM\Column(name: 'daily_rate', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'Daily rate is required.')]
    #[Assert\PositiveOrZero(message: 'Daily rate cannot be negative.')]
    private ?float $dailyRate = 0.0;

    #[ORM\Column(name: 'total_days', nullable: true)]
    private ?int $totalDays = 0;

    #[ORM\Column(name: 'total_cost', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?float $totalCost = 0.0;

    #[ORM\Column(name: 'security_deposit', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?float $securityDeposit = 0.0;

    #[ORM\Column(name: 'late_fee', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?float $lateFee = 0.0;

    #[ORM\Column(name: 'requires_delivery', nullable: true)]
    private ?bool $requiresDelivery = false;

    #[ORM\Column(name: 'delivery_fee', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Delivery fee cannot be negative.')]
    private ?float $deliveryFee = 0.0;

    #[ORM\Column(name: 'delivery_address', length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'Delivery address is too long.')]
    private ?string $deliveryAddress = null;

    #[ORM\Column(name: 'rental_status', length: 20)]
    #[Assert\Choice(choices: self::RENTAL_STATUSES, message: 'Choose a valid rental status.')]
    private ?string $rentalStatus = 'PENDING';

    #[ORM\Column(name: 'pickup_condition', length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Pickup condition is too long.')]
    private ?string $pickupCondition = null;

    #[ORM\Column(name: 'return_condition', length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Return condition is too long.')]
    private ?string $returnCondition = null;

    #[ORM\Column(name: 'pickup_photos', type: Types::TEXT, nullable: true)]
    private ?string $pickupPhotos = null;

    #[ORM\Column(name: 'return_photos', type: Types::TEXT, nullable: true)]
    private ?string $returnPhotos = null;

    #[ORM\Column(name: 'damage_notes', type: Types::TEXT, nullable: true)]
    private ?string $damageNotes = null;

    #[ORM\Column(name: 'owner_rating', nullable: true)]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Owner rating must be between {{ min }} and {{ max }}.')]
    private ?int $ownerRating = null;

    #[ORM\Column(name: 'renter_rating', nullable: true)]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Renter rating must be between {{ min }} and {{ max }}.')]
    private ?int $renterRating = null;

    #[ORM\Column(name: 'owner_review', type: Types::TEXT, nullable: true)]
    private ?string $ownerReview = null;

    #[ORM\Column(name: 'renter_review', type: Types::TEXT, nullable: true)]
    private ?string $renterReview = null;

    #[ORM\Column(name: 'payment_status', length: 20)]
    #[Assert\Choice(choices: self::PAYMENT_STATUSES, message: 'Choose a valid payment status.')]
    private ?string $paymentStatus = 'PENDING';

    #[ORM\Column(name: 'payment_method', length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Payment method is too long.')]
    private ?string $paymentMethod = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(?Inventory $inventory): static
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function setOwnerName(string $ownerName): static
    {
        $this->ownerName = trim($ownerName);

        return $this;
    }

    public function getRenterName(): ?string
    {
        return $this->renterName;
    }

    public function setRenterName(?string $renterName): static
    {
        $this->renterName = $renterName !== null ? trim($renterName) : null;

        return $this;
    }

    public function getRenterContact(): ?string
    {
        return $this->renterContact;
    }

    public function setRenterContact(?string $renterContact): static
    {
        $this->renterContact = $renterContact !== null ? trim($renterContact) : null;

        return $this;
    }

    public function getRenterAddress(): ?string
    {
        return $this->renterAddress;
    }

    public function setRenterAddress(?string $renterAddress): static
    {
        $this->renterAddress = $renterAddress !== null ? trim($renterAddress) : null;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getActualReturnDate(): ?\DateTimeInterface
    {
        return $this->actualReturnDate;
    }

    public function setActualReturnDate(?\DateTimeInterface $actualReturnDate): static
    {
        $this->actualReturnDate = $actualReturnDate;

        return $this;
    }

    public function getDailyRate(): ?float
    {
        return $this->dailyRate;
    }

    public function setDailyRate(?float $dailyRate): static
    {
        $this->dailyRate = $dailyRate;

        return $this;
    }

    public function getTotalDays(): ?int
    {
        return $this->totalDays;
    }

    public function setTotalDays(?int $totalDays): static
    {
        $this->totalDays = $totalDays;

        return $this;
    }

    public function getTotalCost(): ?float
    {
        return $this->totalCost;
    }

    public function setTotalCost(?float $totalCost): static
    {
        $this->totalCost = $totalCost;

        return $this;
    }

    public function getSecurityDeposit(): ?float
    {
        return $this->securityDeposit;
    }

    public function setSecurityDeposit(?float $securityDeposit): static
    {
        $this->securityDeposit = $securityDeposit;

        return $this;
    }

    public function getLateFee(): ?float
    {
        return $this->lateFee;
    }

    public function setLateFee(?float $lateFee): static
    {
        $this->lateFee = $lateFee;

        return $this;
    }

    public function isRequiresDelivery(): ?bool
    {
        return $this->requiresDelivery;
    }

    public function setRequiresDelivery(?bool $requiresDelivery): static
    {
        $this->requiresDelivery = $requiresDelivery;

        return $this;
    }

    public function getDeliveryFee(): ?float
    {
        return $this->deliveryFee;
    }

    public function setDeliveryFee(?float $deliveryFee): static
    {
        $this->deliveryFee = $deliveryFee;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress !== null ? trim($deliveryAddress) : null;

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

    public function getPickupCondition(): ?string
    {
        return $this->pickupCondition;
    }

    public function setPickupCondition(?string $pickupCondition): static
    {
        $this->pickupCondition = $pickupCondition !== null ? trim($pickupCondition) : null;

        return $this;
    }

    public function getReturnCondition(): ?string
    {
        return $this->returnCondition;
    }

    public function setReturnCondition(?string $returnCondition): static
    {
        $this->returnCondition = $returnCondition !== null ? trim($returnCondition) : null;

        return $this;
    }

    public function getPickupPhotos(): ?string
    {
        return $this->pickupPhotos;
    }

    public function setPickupPhotos(?string $pickupPhotos): static
    {
        $this->pickupPhotos = $pickupPhotos;

        return $this;
    }

    public function getReturnPhotos(): ?string
    {
        return $this->returnPhotos;
    }

    public function setReturnPhotos(?string $returnPhotos): static
    {
        $this->returnPhotos = $returnPhotos;

        return $this;
    }

    public function getDamageNotes(): ?string
    {
        return $this->damageNotes;
    }

    public function setDamageNotes(?string $damageNotes): static
    {
        $this->damageNotes = $damageNotes;

        return $this;
    }

    public function getOwnerRating(): ?int
    {
        return $this->ownerRating;
    }

    public function setOwnerRating(?int $ownerRating): static
    {
        $this->ownerRating = $ownerRating;

        return $this;
    }

    public function getRenterRating(): ?int
    {
        return $this->renterRating;
    }

    public function setRenterRating(?int $renterRating): static
    {
        $this->renterRating = $renterRating;

        return $this;
    }

    public function getOwnerReview(): ?string
    {
        return $this->ownerReview;
    }

    public function setOwnerReview(?string $ownerReview): static
    {
        $this->ownerReview = $ownerReview;

        return $this;
    }

    public function getRenterReview(): ?string
    {
        return $this->renterReview;
    }

    public function setRenterReview(?string $renterReview): static
    {
        $this->renterReview = $renterReview;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod !== null ? trim($paymentMethod) : null;

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

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->inventory && !$this->inventory->isRentable()) {
            $context->buildViolation('This inventory item is not marked as rentable.')
                ->atPath('inventory')
                ->addViolation();
        }

        if ($this->startDate && $this->endDate && $this->endDate < $this->startDate) {
            $context->buildViolation('End date must be on or after the start date.')
                ->atPath('endDate')
                ->addViolation();
        }

        if (($this->requiresDelivery ?? false) && ($this->deliveryAddress === null || trim($this->deliveryAddress) === '')) {
            $context->buildViolation('Delivery address is required when delivery is enabled.')
                ->atPath('deliveryAddress')
                ->addViolation();
        }

        if ($this->actualReturnDate && $this->startDate && $this->actualReturnDate < $this->startDate) {
            $context->buildViolation('Actual return date cannot be before the start date.')
                ->atPath('actualReturnDate')
                ->addViolation();
        }
    }

    public function isOverdue(): bool
    {
        return $this->actualReturnDate === null
            && in_array($this->rentalStatus, ['ACTIVE', 'APPROVED'], true)
            && $this->endDate !== null
            && $this->endDate < new \DateTime('today');
    }

    public function getDaysRemaining(): int
    {
        if (!$this->endDate) {
            return 0;
        }

        $diff = (new \DateTime('today'))->diff($this->endDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    public function getDisplayItemName(): string
    {
        return $this->inventory?->getItemName() ?? 'Unknown item';
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
