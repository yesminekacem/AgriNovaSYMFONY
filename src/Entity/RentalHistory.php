<?php

namespace App\Entity;

use App\Repository\RentalHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RentalHistoryRepository::class)]
#[ORM\Table(name: 'rental_history')]
class RentalHistory
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $historyId = null;

    #[ORM\Column(type: 'integer')]
    private int $rentalId;

    #[ORM\Column(type: 'string')]
    private string $actionType;

    #[ORM\Column(type: 'text', nullable: true, length: 65535)]
    private ?string $actionDescription = null;

    #[ORM\Column(type: 'string', nullable: true, length: 255)]
    private ?string $performedBy = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $actionTimestamp;


    public function getHistoryId(): ?int
    {
        return $this->historyId;
    }

    public function getRentalId(): int
    {
        return $this->rentalId;
    }

    public function setRentalId(int $rentalId): self
    {
        $this->rentalId = $rentalId;
        return $this;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): self
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function getActionDescription(): ?string
    {
        return $this->actionDescription;
    }

    public function setActionDescription(?string $actionDescription): self
    {
        $this->actionDescription = $actionDescription;
        return $this;
    }

    public function getPerformedBy(): ?string
    {
        return $this->performedBy;
    }

    public function setPerformedBy(?string $performedBy): self
    {
        $this->performedBy = $performedBy;
        return $this;
    }

    public function getActionTimestamp(): \DateTimeInterface
    {
        return $this->actionTimestamp;
    }

    public function setActionTimestamp(\DateTimeInterface $actionTimestamp): self
    {
        $this->actionTimestamp = $actionTimestamp;
        return $this;
    }

}
