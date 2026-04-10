<?php

namespace App\Entity;

use App\Repository\ProductListingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductListingRepository::class)]
#[ORM\Table(name: 'product_listing')]
class ProductListing
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $listingId = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $productName;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $pricePerUnit;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: 'text', nullable: true, length: 65535)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', nullable: true, length: 255)]
    private ?string $picture = null;

    #[ORM\Column(type: 'string', nullable: true, length: 50)]
    private ?string $category = null;


    public function getListingId(): ?int
    {
        return $this->listingId;
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

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): self
    {
        $this->productName = $productName;
        return $this;
    }

    public function getPricePerUnit(): float
    {
        return $this->pricePerUnit;
    }

    public function setPricePerUnit(float $pricePerUnit): self
    {
        $this->pricePerUnit = $pricePerUnit;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
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

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

}
