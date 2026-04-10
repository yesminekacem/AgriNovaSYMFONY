<?php

namespace App\Entity;

use App\Repository\RentalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RentalRepository::class)]
#[ORM\Table(name: 'rental')]
class Rental
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $rentalId = null;

    #[ORM\ManyToOne(targetEntity: Inventory::class)]
    #[ORM\JoinColumn(name: 'inventory_id', referencedColumnName: 'inventory_id', nullable: false)]
    private ?Inventory $inventory = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $ownerName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $renterName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $renterContact;

    #[ORM\Column(type: 'string', nullable: true, length: 500)]
    private ?string $renterAddress = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $endDate;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $actualReturnDate = null;

    #[ORM\Column(type: 'float', precision: 22)]
    private float $dailyRate;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $totalDays = null;

    #[ORM\Column(type: 'float', nullable: true, precision: 22)]
    private ?float $totalCost = null;

    #[ORM\Column(type: 'float', nullable: true, precision: 22)]
    private ?float $securityDeposit = null;

    #[ORM\Column(type: 'float', nullable: true, precision: 22)]
    private ?float $lateFee = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $requiresDelivery = null;

    #[ORM\Column(type: 'float', nullable: true, precision: 22)]
    private ?float $deliveryFee = null;

    #[ORM\Column(type: 'string', nullable: true, length: 500)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(type: 'string')]
    private string $rentalStatus;

    #[ORM\Column(type: 'string', nullable: true, length: 255)]
    private ?string $pickupCondition = null;

    #[ORM\Column(type: 'string', nullable: true, length: 255)]
    private ?string $returnCondition = null;

    #[ORM\Column(type: 'text', nullable: true, length: 65535)]
    private ?string $pickupPhotos = null;

    #[ORM\Column(type: 'text', nullable: true, length: 65535)]
    private ?string $returnPhotos = null;

    #[ORM\Column(type: 'text', nullable: true, length: 65535)]
    private ?string $damageNotes = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $ownerRating = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $renterRating = null;

    #[ORM\Column(type: 'text', nullable: true, length: 65535)]
    private ?string $ownerReview = null;

    #[ORM\Column(type: 'text', nullable: true, length: 65535)]
    private ?string $renterReview = null;

    #[ORM\Column(type: 'string')]
    private string $paymentStatus;

    #[ORM\Column(type: 'string', nullable: true, length: 100)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;


    public function getRentalId(): ?int
    {
        return $this->rentalId;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(?Inventory $inventory): self
    {
        $this->inventory = $inventory;
        return $this;
    }

    public function getOwnerName(): string
    {
        return $this->ownerName;
    }

    public function setOwnerName(string $ownerName): self
    {
        $this->ownerName = $ownerName;
        return $this;
    }

    public function getRenterName(): string
    {
        return $this->renterName;
    }

    public function setRenterName(string $renterName): self
    {
        $this->renterName = $renterName;
        return $this;
    }

    public function getRenterContact(): string
    {
        return $this->renterContact;
    }

    public function setRenterContact(string $renterContact): self
    {
        $this->renterContact = $renterContact;
        return $this;
    }

    public function getRenterAddress(): ?string
    {
        return $this->renterAddress;
    }

    public function setRenterAddress(?string $renterAddress): self
    {
        $this->renterAddress = $renterAddress;
        return $this;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getActualReturnDate(): ?\DateTimeInterface
    {
        return $this->actualReturnDate;
    }

    public function setActualReturnDate(?\DateTimeInterface $actualReturnDate): self
    {
        $this->actualReturnDate = $actualReturnDate;
        return $this;
    }

    public function getDailyRate(): float
    {
        return $this->dailyRate;
    }

    public function setDailyRate(float $dailyRate): self
    {
        $this->dailyRate = $dailyRate;
        return $this;
    }

    public function getTotalDays(): ?int
    {
        return $this->totalDays;
    }

    public function setTotalDays(?int $totalDays): self
    {
        $this->totalDays = $totalDays;
        return $this;
    }

    public function getTotalCost(): ?float
    {
        return $this->totalCost;
    }

    public function setTotalCost(?float $totalCost): self
    {
        $this->totalCost = $totalCost;
        return $this;
    }

    public function getSecurityDeposit(): ?float
    {
        return $this->securityDeposit;
    }

    public function setSecurityDeposit(?float $securityDeposit): self
    {
        $this->securityDeposit = $securityDeposit;
        return $this;
    }

    public function getLateFee(): ?float
    {
        return $this->lateFee;
    }

    public function setLateFee(?float $lateFee): self
    {
        $this->lateFee = $lateFee;
        return $this;
    }

    public function getRequiresDelivery(): ?bool
    {
        return $this->requiresDelivery;
    }

    public function setRequiresDelivery(?bool $requiresDelivery): self
    {
        $this->requiresDelivery = $requiresDelivery;
        return $this;
    }

    public function getDeliveryFee(): ?float
    {
        return $this->deliveryFee;
    }

    public function setDeliveryFee(?float $deliveryFee): self
    {
        $this->deliveryFee = $deliveryFee;
        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;
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

    public function getPickupCondition(): ?string
    {
        return $this->pickupCondition;
    }

    public function setPickupCondition(?string $pickupCondition): self
    {
        $this->pickupCondition = $pickupCondition;
        return $this;
    }

    public function getReturnCondition(): ?string
    {
        return $this->returnCondition;
    }

    public function setReturnCondition(?string $returnCondition): self
    {
        $this->returnCondition = $returnCondition;
        return $this;
    }

    public function getPickupPhotos(): ?string
    {
        return $this->pickupPhotos;
    }

    public function setPickupPhotos(?string $pickupPhotos): self
    {
        $this->pickupPhotos = $pickupPhotos;
        return $this;
    }

    public function getReturnPhotos(): ?string
    {
        return $this->returnPhotos;
    }

    public function setReturnPhotos(?string $returnPhotos): self
    {
        $this->returnPhotos = $returnPhotos;
        return $this;
    }

    public function getDamageNotes(): ?string
    {
        return $this->damageNotes;
    }

    public function setDamageNotes(?string $damageNotes): self
    {
        $this->damageNotes = $damageNotes;
        return $this;
    }

    public function getOwnerRating(): ?int
    {
        return $this->ownerRating;
    }

    public function setOwnerRating(?int $ownerRating): self
    {
        $this->ownerRating = $ownerRating;
        return $this;
    }

    public function getRenterRating(): ?int
    {
        return $this->renterRating;
    }

    public function setRenterRating(?int $renterRating): self
    {
        $this->renterRating = $renterRating;
        return $this;
    }

    public function getOwnerReview(): ?string
    {
        return $this->ownerReview;
    }

    public function setOwnerReview(?string $ownerReview): self
    {
        $this->ownerReview = $ownerReview;
        return $this;
    }

    public function getRenterReview(): ?string
    {
        return $this->renterReview;
    }

    public function setRenterReview(?string $renterReview): self
    {
        $this->renterReview = $renterReview;
        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
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

}
