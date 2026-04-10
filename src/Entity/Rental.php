<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Rental
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $rental_id;

    #[ORM\Column(type: "integer")]
    private int $inventory_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $owner_name;

    #[ORM\Column(type: "string", length: 255)]
    private string $renter_name;

    #[ORM\Column(type: "string", length: 100)]
    private string $renter_contact;

    #[ORM\Column(type: "string", length: 500)]
    private string $renter_address;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $start_date;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $end_date;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $actual_return_date;

    #[ORM\Column(type: "float")]
    private float $daily_rate;

    #[ORM\Column(type: "integer")]
    private int $total_days;

    #[ORM\Column(type: "float")]
    private float $total_cost;

    #[ORM\Column(type: "float")]
    private float $security_deposit;

    #[ORM\Column(type: "float")]
    private float $late_fee;

    #[ORM\Column(type: "boolean")]
    private bool $requires_delivery;

    #[ORM\Column(type: "float")]
    private float $delivery_fee;

    #[ORM\Column(type: "string", length: 500)]
    private string $delivery_address;

    #[ORM\Column(type: "string")]
    private string $rental_status;

    #[ORM\Column(type: "string", length: 255)]
    private string $pickup_condition;

    #[ORM\Column(type: "string", length: 255)]
    private string $return_condition;

    #[ORM\Column(type: "text")]
    private string $pickup_photos;

    #[ORM\Column(type: "text")]
    private string $return_photos;

    #[ORM\Column(type: "text")]
    private string $damage_notes;

    #[ORM\Column(type: "integer")]
    private int $owner_rating;

    #[ORM\Column(type: "integer")]
    private int $renter_rating;

    #[ORM\Column(type: "text")]
    private string $owner_review;

    #[ORM\Column(type: "text")]
    private string $renter_review;

    #[ORM\Column(type: "string")]
    private string $payment_status;

    #[ORM\Column(type: "string", length: 100)]
    private string $payment_method;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $updated_at;

    public function getRental_id()
    {
        return $this->rental_id;
    }

    public function setRental_id($value)
    {
        $this->rental_id = $value;
    }

    public function getInventory_id()
    {
        return $this->inventory_id;
    }

    public function setInventory_id($value)
    {
        $this->inventory_id = $value;
    }

    public function getOwner_name()
    {
        return $this->owner_name;
    }

    public function setOwner_name($value)
    {
        $this->owner_name = $value;
    }

    public function getRenter_name()
    {
        return $this->renter_name;
    }

    public function setRenter_name($value)
    {
        $this->renter_name = $value;
    }

    public function getRenter_contact()
    {
        return $this->renter_contact;
    }

    public function setRenter_contact($value)
    {
        $this->renter_contact = $value;
    }

    public function getRenter_address()
    {
        return $this->renter_address;
    }

    public function setRenter_address($value)
    {
        $this->renter_address = $value;
    }

    public function getStart_date()
    {
        return $this->start_date;
    }

    public function setStart_date($value)
    {
        $this->start_date = $value;
    }

    public function getEnd_date()
    {
        return $this->end_date;
    }

    public function setEnd_date($value)
    {
        $this->end_date = $value;
    }

    public function getActual_return_date()
    {
        return $this->actual_return_date;
    }

    public function setActual_return_date($value)
    {
        $this->actual_return_date = $value;
    }

    public function getDaily_rate()
    {
        return $this->daily_rate;
    }

    public function setDaily_rate($value)
    {
        $this->daily_rate = $value;
    }

    public function getTotal_days()
    {
        return $this->total_days;
    }

    public function setTotal_days($value)
    {
        $this->total_days = $value;
    }

    public function getTotal_cost()
    {
        return $this->total_cost;
    }

    public function setTotal_cost($value)
    {
        $this->total_cost = $value;
    }

    public function getSecurity_deposit()
    {
        return $this->security_deposit;
    }

    public function setSecurity_deposit($value)
    {
        $this->security_deposit = $value;
    }

    public function getLate_fee()
    {
        return $this->late_fee;
    }

    public function setLate_fee($value)
    {
        $this->late_fee = $value;
    }

    public function getRequires_delivery()
    {
        return $this->requires_delivery;
    }

    public function setRequires_delivery($value)
    {
        $this->requires_delivery = $value;
    }

    public function getDelivery_fee()
    {
        return $this->delivery_fee;
    }

    public function setDelivery_fee($value)
    {
        $this->delivery_fee = $value;
    }

    public function getDelivery_address()
    {
        return $this->delivery_address;
    }

    public function setDelivery_address($value)
    {
        $this->delivery_address = $value;
    }

    public function getRental_status()
    {
        return $this->rental_status;
    }

    public function setRental_status($value)
    {
        $this->rental_status = $value;
    }

    public function getPickup_condition()
    {
        return $this->pickup_condition;
    }

    public function setPickup_condition($value)
    {
        $this->pickup_condition = $value;
    }

    public function getReturn_condition()
    {
        return $this->return_condition;
    }

    public function setReturn_condition($value)
    {
        $this->return_condition = $value;
    }

    public function getPickup_photos()
    {
        return $this->pickup_photos;
    }

    public function setPickup_photos($value)
    {
        $this->pickup_photos = $value;
    }

    public function getReturn_photos()
    {
        return $this->return_photos;
    }

    public function setReturn_photos($value)
    {
        $this->return_photos = $value;
    }

    public function getDamage_notes()
    {
        return $this->damage_notes;
    }

    public function setDamage_notes($value)
    {
        $this->damage_notes = $value;
    }

    public function getOwner_rating()
    {
        return $this->owner_rating;
    }

    public function setOwner_rating($value)
    {
        $this->owner_rating = $value;
    }

    public function getRenter_rating()
    {
        return $this->renter_rating;
    }

    public function setRenter_rating($value)
    {
        $this->renter_rating = $value;
    }

    public function getOwner_review()
    {
        return $this->owner_review;
    }

    public function setOwner_review($value)
    {
        $this->owner_review = $value;
    }

    public function getRenter_review()
    {
        return $this->renter_review;
    }

    public function setRenter_review($value)
    {
        $this->renter_review = $value;
    }

    public function getPayment_status()
    {
        return $this->payment_status;
    }

    public function setPayment_status($value)
    {
        $this->payment_status = $value;
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

    public function getUpdated_at()
    {
        return $this->updated_at;
    }

    public function setUpdated_at($value)
    {
        $this->updated_at = $value;
    }
}
