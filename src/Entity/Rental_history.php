<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Rental_history
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $history_id;

    #[ORM\Column(type: "integer")]
    private int $rental_id;

    #[ORM\Column(type: "string")]
    private string $action_type;

    #[ORM\Column(type: "text")]
    private string $action_description;

    #[ORM\Column(type: "string", length: 255)]
    private string $performed_by;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $action_timestamp;

    public function getHistory_id()
    {
        return $this->history_id;
    }

    public function setHistory_id($value)
    {
        $this->history_id = $value;
    }

    public function getRental_id()
    {
        return $this->rental_id;
    }

    public function setRental_id($value)
    {
        $this->rental_id = $value;
    }

    public function getAction_type()
    {
        return $this->action_type;
    }

    public function setAction_type($value)
    {
        $this->action_type = $value;
    }

    public function getAction_description()
    {
        return $this->action_description;
    }

    public function setAction_description($value)
    {
        $this->action_description = $value;
    }

    public function getPerformed_by()
    {
        return $this->performed_by;
    }

    public function setPerformed_by($value)
    {
        $this->performed_by = $value;
    }

    public function getAction_timestamp()
    {
        return $this->action_timestamp;
    }

    public function setAction_timestamp($value)
    {
        $this->action_timestamp = $value;
    }
}
