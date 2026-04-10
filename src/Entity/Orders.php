<?php

namespace App\Entity;

use App\Repository\OrdersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrdersRepository::class)]
#[ORM\Table(name: 'orders')]
class Orders
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $userId;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $orderDate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $totalPrice;

    #[ORM\Column(type: 'string', nullable: true, length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'text', length: 65535)]
    private string $deliveryAddress;

    #[ORM\Column(type: 'string', nullable: true, length: 50)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'float', nullable: true, precision: 22)]
    private ?float $deliveryLat = null;

    #[ORM\Column(type: 'float', nullable: true, precision: 22)]
    private ?float $deliveryLng = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(?\DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDeliveryAddress(): string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDeliveryLat(): ?float
    {
        return $this->deliveryLat;
    }

    public function setDeliveryLat(?float $deliveryLat): self
    {
        $this->deliveryLat = $deliveryLat;
        return $this;
    }

    public function getDeliveryLng(): ?float
    {
        return $this->deliveryLng;
    }

    public function setDeliveryLng(?float $deliveryLng): self
    {
        $this->deliveryLng = $deliveryLng;
        return $this;
    }

}
