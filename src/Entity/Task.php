<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $taskId = null;

    #[ORM\ManyToOne(targetEntity: Crop::class)]
    #[ORM\JoinColumn(name: 'crop_id', referencedColumnName: 'crop_id', nullable: false)]
    private ?Crop $crop = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $taskName;

    #[ORM\Column(type: 'text', nullable: true, length: 65535)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', nullable: true, length: 50)]
    private ?string $taskType = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $scheduledDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $completedDate = null;

    #[ORM\Column(type: 'string', nullable: true, length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'string', nullable: true, length: 100)]
    private ?string $assignedTo = null;

    #[ORM\Column(type: 'decimal', nullable: true, precision: 10, scale: 2)]
    private ?float $cost = null;


    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function getCrop(): ?Crop
    {
        return $this->crop;
    }

    public function setCrop(?Crop $crop): self
    {
        $this->crop = $crop;
        return $this;
    }

    public function getTaskName(): string
    {
        return $this->taskName;
    }

    public function setTaskName(string $taskName): self
    {
        $this->taskName = $taskName;
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

    public function getTaskType(): ?string
    {
        return $this->taskType;
    }

    public function setTaskType(?string $taskType): self
    {
        $this->taskType = $taskType;
        return $this;
    }

    public function getScheduledDate(): ?\DateTimeInterface
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(?\DateTimeInterface $scheduledDate): self
    {
        $this->scheduledDate = $scheduledDate;
        return $this;
    }

    public function getCompletedDate(): ?\DateTimeInterface
    {
        return $this->completedDate;
    }

    public function setCompletedDate(?\DateTimeInterface $completedDate): self
    {
        $this->completedDate = $completedDate;
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

    public function getAssignedTo(): ?string
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?string $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(?float $cost): self
    {
        $this->cost = $cost;
        return $this;
    }

}
