<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Inventory
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $inventory_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $item_name;

    #[ORM\Column(type: "string")]
    private string $item_type;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "integer")]
    private int $quantity;

    #[ORM\Column(type: "float")]
    private float $unit_price;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $purchase_date;

    #[ORM\Column(type: "string")]
    private string $condition_status;

    #[ORM\Column(type: "boolean")]
    private bool $is_rentable;

    #[ORM\Column(type: "float")]
    private float $rental_price_per_day;

    #[ORM\Column(type: "string")]
    private string $rental_status;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $last_maintenance_date;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $next_maintenance_date;

    #[ORM\Column(type: "integer")]
    private int $total_usage_hours;

    #[ORM\Column(type: "string", length: 255)]
    private string $owner_name;

    #[ORM\Column(type: "string", length: 100)]
    private string $owner_contact;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $updated_at;

    #[ORM\Column(type: "string", length: 500)]
    private string $image_path;

    public function getInventory_id()
    {
        return $this->inventory_id;
    }

    public function setInventory_id($value)
    {
        $this->inventory_id = $value;
    }

    public function getItem_name()
    {
        return $this->item_name;
    }

    public function setItem_name($value)
    {
        $this->item_name = $value;
    }

    public function getItem_type()
    {
        return $this->item_type;
    }

    public function setItem_type($value)
    {
        $this->item_type = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($value)
    {
        $this->quantity = $value;
    }

    public function getUnit_price()
    {
        return $this->unit_price;
    }

    public function setUnit_price($value)
    {
        $this->unit_price = $value;
    }

    public function getPurchase_date()
    {
        return $this->purchase_date;
    }

    public function setPurchase_date($value)
    {
        $this->purchase_date = $value;
    }

    public function getCondition_status()
    {
        return $this->condition_status;
    }

    public function setCondition_status($value)
    {
        $this->condition_status = $value;
    }

    public function getIs_rentable()
    {
        return $this->is_rentable;
    }

    public function setIs_rentable($value)
    {
        $this->is_rentable = $value;
    }

    public function getRental_price_per_day()
    {
        return $this->rental_price_per_day;
    }

    public function setRental_price_per_day($value)
    {
        $this->rental_price_per_day = $value;
    }

    public function getRental_status()
    {
        return $this->rental_status;
    }

    public function setRental_status($value)
    {
        $this->rental_status = $value;
    }

    public function getLast_maintenance_date()
    {
        return $this->last_maintenance_date;
    }

    public function setLast_maintenance_date($value)
    {
        $this->last_maintenance_date = $value;
    }

    public function getNext_maintenance_date()
    {
        return $this->next_maintenance_date;
    }

    public function setNext_maintenance_date($value)
    {
        $this->next_maintenance_date = $value;
    }

    public function getTotal_usage_hours()
    {
        return $this->total_usage_hours;
    }

    public function setTotal_usage_hours($value)
    {
        $this->total_usage_hours = $value;
    }

    public function getOwner_name()
    {
        return $this->owner_name;
    }

    public function setOwner_name($value)
    {
        $this->owner_name = $value;
    }

    public function getOwner_contact()
    {
        return $this->owner_contact;
    }

    public function setOwner_contact($value)
    {
        $this->owner_contact = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getUpdated_at()
    {
        return $this->updated_at;
    }

    public function setUpdated_at($value)
    {
        $this->updated_at = $value;
    }

    public function getImage_path()
    {
        return $this->image_path;
    }

    public function setImage_path($value)
    {
        $this->image_path = $value;
    }
}
