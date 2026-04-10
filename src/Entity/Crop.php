<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Task;

#[ORM\Entity]
class Crop
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $crop_id;

    #[ORM\Column(type: "string", length: 100)]
    private string $name;

    #[ORM\Column(type: "string", length: 50)]
    private string $type;

    #[ORM\Column(type: "string", length: 100)]
    private string $variety;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $planting_date;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $expected_harvest_date;

    #[ORM\Column(type: "string", length: 50)]
    private string $growth_stage;

    #[ORM\Column(type: "float")]
    private float $area_size;

    #[ORM\Column(type: "string", length: 50)]
    private string $status;

    #[ORM\Column(type: "string", length: 255)]
    private string $image_path;

    public function getCrop_id()
    {
        return $this->crop_id;
    }

    public function setCrop_id($value)
    {
        $this->crop_id = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function getVariety()
    {
        return $this->variety;
    }

    public function setVariety($value)
    {
        $this->variety = $value;
    }

    public function getPlanting_date()
    {
        return $this->planting_date;
    }

    public function setPlanting_date($value)
    {
        $this->planting_date = $value;
    }

    public function getExpected_harvest_date()
    {
        return $this->expected_harvest_date;
    }

    public function setExpected_harvest_date($value)
    {
        $this->expected_harvest_date = $value;
    }

    public function getGrowth_stage()
    {
        return $this->growth_stage;
    }

    public function setGrowth_stage($value)
    {
        $this->growth_stage = $value;
    }

    public function getArea_size()
    {
        return $this->area_size;
    }

    public function setArea_size($value)
    {
        $this->area_size = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getImage_path()
    {
        return $this->image_path;
    }

    public function setImage_path($value)
    {
        $this->image_path = $value;
    }

    #[ORM\OneToMany(mappedBy: "crop_id", targetEntity: Task::class)]
    private Collection $tasks;

        public function getTasks(): Collection
        {
            return $this->tasks;
        }
    
        public function addTask(Task $task): self
        {
            if (!$this->tasks->contains($task)) {
                $this->tasks[] = $task;
                $task->setCrop_id($this);
            }
    
            return $this;
        }
    
        public function removeTask(Task $task): self
        {
            if ($this->tasks->removeElement($task)) {
                // set the owning side to null (unless already changed)
                if ($task->getCrop_id() === $this) {
                    $task->setCrop_id(null);
                }
            }
    
            return $this;
        }
}
