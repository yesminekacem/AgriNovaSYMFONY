<?php

namespace App\Entity;

use App\Repository\RentalHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RentalHistoryRepository::class)]
#[ORM\Table(name: 'rental_history')]
#[ORM\HasLifecycleCallbacks]
class RentalHistory
{
    public const ACTION_TYPES = ['CREATED', 'APPROVED', 'ACTIVATED', 'RETURNED', 'COMPLETED', 'CANCELLED', 'UPDATED', 'DISPUTED'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'history_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Rental::class)]
    #[ORM\JoinColumn(name: 'rental_id', referencedColumnName: 'rental_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Rental $rental = null;

    #[ORM\Column(name: 'action_type', length: 20)]
    #[Assert\Choice(choices: self::ACTION_TYPES, message: 'Choose a valid action type.')]
    private ?string $actionType = null;

    #[ORM\Column(name: 'action_description', type: Types::TEXT, nullable: true)]
    private ?string $actionDescription = null;

    #[ORM\Column(name: 'performed_by', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $performedBy = null;

    #[ORM\Column(name: 'action_timestamp', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $actionTimestamp = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRental(): ?Rental
    {
        return $this->rental;
    }

    public function setRental(?Rental $rental): static
    {
        $this->rental = $rental;

        return $this;
    }

    public function getActionType(): ?string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): static
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getActionDescription(): ?string
    {
        return $this->actionDescription;
    }

    public function setActionDescription(?string $actionDescription): static
    {
        $this->actionDescription = $actionDescription;

        return $this;
    }

    public function getPerformedBy(): ?string
    {
        return $this->performedBy;
    }

    public function setPerformedBy(?string $performedBy): static
    {
        $this->performedBy = $performedBy !== null ? trim($performedBy) : null;

        return $this;
    }

    public function getActionTimestamp(): ?\DateTimeInterface
    {
        return $this->actionTimestamp;
    }

    public function setActionTimestamp(?\DateTimeInterface $actionTimestamp): static
    {
        $this->actionTimestamp = $actionTimestamp;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->actionTimestamp ??= new \DateTime();
    }
}
