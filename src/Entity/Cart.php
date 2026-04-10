<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Product_listing;

#[ORM\Entity]
class Cart
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 50)]
    private string $user_id;

        #[ORM\ManyToOne(targetEntity: Product_listing::class, inversedBy: "carts")]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'listing_id', onDelete: 'CASCADE')]
    private Product_listing $product_id;

    #[ORM\Column(type: "integer")]
    private int $quantity;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $added_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getProduct_id()
    {
        return $this->product_id;
    }

    public function setProduct_id($value)
    {
        $this->product_id = $value;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($value)
    {
        $this->quantity = $value;
    }

    public function getAdded_at()
    {
        return $this->added_at;
    }

    public function setAdded_at($value)
    {
        $this->added_at = $value;
    }
}
