<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Order_items;

#[ORM\Entity]
class Product_listing
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $listing_id;

    #[ORM\Column(type: "string", length: 50)]
    private string $user_id;

    #[ORM\Column(type: "string", length: 100)]
    private string $product_name;

    #[ORM\Column(type: "float")]
    private float $price_per_unit;

    #[ORM\Column(type: "integer")]
    private int $quantity;

    #[ORM\Column(type: "string")]
    private string $status;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string", length: 255)]
    private string $picture;

    #[ORM\Column(type: "string", length: 50)]
    private string $category;

    public function getListing_id()
    {
        return $this->listing_id;
    }

    public function setListing_id($value)
    {
        $this->listing_id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getProduct_name()
    {
        return $this->product_name;
    }

    public function setProduct_name($value)
    {
        $this->product_name = $value;
    }

    public function getPrice_per_unit()
    {
        return $this->price_per_unit;
    }

    public function setPrice_per_unit($value)
    {
        $this->price_per_unit = $value;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($value)
    {
        $this->quantity = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getPicture()
    {
        return $this->picture;
    }

    public function setPicture($value)
    {
        $this->picture = $value;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($value)
    {
        $this->category = $value;
    }

    #[ORM\OneToMany(mappedBy: "product_id", targetEntity: Cart::class)]
    private Collection $carts;

        public function getCarts(): Collection
        {
            return $this->carts;
        }
    
        public function addCart(Cart $cart): self
        {
            if (!$this->carts->contains($cart)) {
                $this->carts[] = $cart;
                $cart->setProduct_id($this);
            }
    
            return $this;
        }
    
        public function removeCart(Cart $cart): self
        {
            if ($this->carts->removeElement($cart)) {
                // set the owning side to null (unless already changed)
                if ($cart->getProduct_id() === $this) {
                    $cart->setProduct_id(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "product_id", targetEntity: Order_items::class)]
    private Collection $order_itemss;
}
