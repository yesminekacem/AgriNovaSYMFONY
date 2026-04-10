<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $taskId = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: "Task name is required")]
    #[Assert\Length(min: 3, minMessage: "Task name must be at least 3 characters")]
    private string $taskName;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Description is required")]
    private string $description;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: "Task type is required")]
    private string $taskType;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: "Scheduled date is required")]
    #[Assert\Type(\DateTimeInterface::class)]
    private \DateTimeInterface $scheduledDate;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Assert\GreaterThanOrEqual(
        propertyPath: "scheduledDate",
        message: "Completed date must be after scheduled date"
    )]
    private ?\DateTimeInterface $completedDate = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: "Status is required")]
    private string $status;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: "Assigned person is required")]
    private string $assignedTo;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Cost is required")]
    #[Assert\Positive(message: "Cost must be positive")]
    private float $cost;

    #[ORM\ManyToOne(targetEntity: Crop::class)]
    #[ORM\JoinColumn(name: 'crop_id', referencedColumnName: 'crop_id', nullable: false)]
    #[Assert\NotNull(message: "Crop must be selected")]
    private ?Crop $crop = null;


   



    public function getTaskId(): ?int
    {
        return $this->taskId;
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getTaskType(): string
    {
        return $this->taskType;
    }

    public function setTaskType(string $taskType): self
    {
        $this->taskType = $taskType;
        return $this;
    }

    public function getScheduledDate(): \DateTimeInterface
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(\DateTimeInterface $scheduledDate): self
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getAssignedTo(): string
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(string $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getCost(): float
    {
        return $this->cost;
    }

    public function setCost(float $cost): self
    {
        $this->cost = $cost;
        return $this;
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

}
