<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Order_items;

#[ORM\Entity]
class Orders
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 50)]
    private string $user_id;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $order_date;

    #[ORM\Column(type: "float")]
    private float $total_price;

    #[ORM\Column(type: "string", length: 50)]
    private string $status;

    #[ORM\Column(type: "text")]
    private string $delivery_address;

    #[ORM\Column(type: "string", length: 50)]
    private string $payment_method;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "float")]
    private float $delivery_lat;

    #[ORM\Column(type: "float")]
    private float $delivery_lng;

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

    public function getOrder_date()
    {
        return $this->order_date;
    }

    public function setOrder_date($value)
    {
        $this->order_date = $value;
    }

    public function getTotal_price()
    {
        return $this->total_price;
    }

    public function setTotal_price($value)
    {
        $this->total_price = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getDelivery_address()
    {
        return $this->delivery_address;
    }

    public function setDelivery_address($value)
    {
        $this->delivery_address = $value;
    }

    public function getPayment_method()
    {
        return $this->payment_method;
    }

    public function setPayment_method($value)
    {
        $this->payment_method = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getDelivery_lat()
    {
        return $this->delivery_lat;
    }

    public function setDelivery_lat($value)
    {
        $this->delivery_lat = $value;
    }

    public function getDelivery_lng()
    {
        return $this->delivery_lng;
    }

    public function setDelivery_lng($value)
    {
        $this->delivery_lng = $value;
    }

    #[ORM\OneToMany(mappedBy: "order_id", targetEntity: Order_items::class)]
    private Collection $order_itemss;

        public function getOrder_itemss(): Collection
        {
            return $this->order_itemss;
        }
    
        public function addOrder_items(Order_items $order_items): self
        {
            if (!$this->order_itemss->contains($order_items)) {
                $this->order_itemss[] = $order_items;
                $order_items->setOrder_id($this);
            }
    
            return $this;
        }
    
        public function removeOrder_items(Order_items $order_items): self
        {
            if ($this->order_itemss->removeElement($order_items)) {
                // set the owning side to null (unless already changed)
                if ($order_items->getOrder_id() === $this) {
                    $order_items->setOrder_id(null);
                }
            }
    
            return $this;
        }
}
