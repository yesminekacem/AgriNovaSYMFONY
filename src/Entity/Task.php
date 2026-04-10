<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Crop;

#[ORM\Entity]
class Task
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $task_id;

        #[ORM\ManyToOne(targetEntity: Crop::class, inversedBy: "tasks")]
    #[ORM\JoinColumn(name: 'crop_id', referencedColumnName: 'crop_id', onDelete: 'CASCADE')]
    private Crop $crop_id;

    #[ORM\Column(type: "string", length: 100)]
    private string $task_name;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string", length: 50)]
    private string $task_type;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $scheduled_date;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $completed_date;

    #[ORM\Column(type: "string", length: 50)]
    private string $status;

    #[ORM\Column(type: "string", length: 100)]
    private string $assigned_to;

    #[ORM\Column(type: "float")]
    private float $cost;

    public function getTask_id()
    {
        return $this->task_id;
    }

    public function setTask_id($value)
    {
        $this->task_id = $value;
    }

    public function getCrop_id()
    {
        return $this->crop_id;
    }

    public function setCrop_id($value)
    {
        $this->crop_id = $value;
    }

    public function getTask_name()
    {
        return $this->task_name;
    }

    public function setTask_name($value)
    {
        $this->task_name = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getTask_type()
    {
        return $this->task_type;
    }

    public function setTask_type($value)
    {
        $this->task_type = $value;
    }

    public function getScheduled_date()
    {
        return $this->scheduled_date;
    }

    public function setScheduled_date($value)
    {
        $this->scheduled_date = $value;
    }

    public function getCompleted_date()
    {
        return $this->completed_date;
    }

    public function setCompleted_date($value)
    {
        $this->completed_date = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getAssigned_to()
    {
        return $this->assigned_to;
    }

    public function setAssigned_to($value)
    {
        $this->assigned_to = $value;
    }

    public function getCost()
    {
        return $this->cost;
    }

    public function setCost($value)
    {
        $this->cost = $value;
    }
}
