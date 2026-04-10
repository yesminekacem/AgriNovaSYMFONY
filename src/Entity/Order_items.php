<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Product_listing;

#[ORM\Entity]
class Order_items
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Orders::class, inversedBy: "order_itemss")]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Orders $order_id;

        #[ORM\ManyToOne(targetEntity: Product_listing::class, inversedBy: "order_itemss")]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'listing_id', onDelete: 'CASCADE')]
    private Product_listing $product_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $product_name;

    #[ORM\Column(type: "integer")]
    private int $quantity;

    #[ORM\Column(type: "float")]
    private float $price_per_unit;

    #[ORM\Column(type: "float")]
    private float $subtotal;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getOrder_id()
    {
        return $this->order_id;
    }

    public function setOrder_id($value)
    {
        $this->order_id = $value;
    }

    public function getProduct_id()
    {
        return $this->product_id;
    }

    public function setProduct_id($value)
    {
        $this->product_id = $value;
    }

    public function getProduct_name()
    {
        return $this->product_name;
    }

    public function setProduct_name($value)
    {
        $this->product_name = $value;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($value)
    {
        $this->quantity = $value;
    }

    public function getPrice_per_unit()
    {
        return $this->price_per_unit;
    }

    public function setPrice_per_unit($value)
    {
        $this->price_per_unit = $value;
    }

    public function getSubtotal()
    {
        return $this->subtotal;
    }

    public function setSubtotal($value)
    {
        $this->subtotal = $value;
    }
}
