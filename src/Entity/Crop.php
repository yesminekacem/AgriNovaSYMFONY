<?php

namespace App\Entity;

use App\Repository\CropRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CropRepository::class)]
#[ORM\Table(name: 'crop')]
class Crop
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $cropId = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Name is required")]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Type is required")]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Variety is required")]
    private string $variety;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: "Planting date is required")]
    #[Assert\Type(\DateTimeInterface::class)]
    private \DateTimeInterface $plantingDate;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: "Harvest date is required")]
    #[Assert\Type(\DateTimeInterface::class)]
    private \DateTimeInterface $expectedHarvestDate;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Growth stage is required")]
    private string $growthStage;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    #[Assert\NotBlank(message: "Area size is required")]
    #[Assert\Positive(message: "Area must be positive")]
    private float $areaSize;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Status is required")]
    private string $status;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $imagePath;


    public function getCropId(): ?int
    {
        return $this->cropId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getVariety(): string
    {
        return $this->variety;
    }

    public function setVariety(string $variety): self
    {
        $this->variety = $variety;
        return $this;
    }

    public function getPlantingDate(): \DateTimeInterface
    {
        return $this->plantingDate;
    }

    public function setPlantingDate(\DateTimeInterface $plantingDate): self
    {
        $this->plantingDate = $plantingDate;
        return $this;
    }

    public function getExpectedHarvestDate(): \DateTimeInterface
    {
        return $this->expectedHarvestDate;
    }

    public function setExpectedHarvestDate(\DateTimeInterface $expectedHarvestDate): self
    {
        $this->expectedHarvestDate = $expectedHarvestDate;
        return $this;
    }

    public function getGrowthStage(): string
    {
        return $this->growthStage;
    }

    public function setGrowthStage(string $growthStage): self
    {
        $this->growthStage = $growthStage;
        return $this;
    }

    public function getAreaSize(): float
    {
        return $this->areaSize;
    }

    public function setAreaSize(float $areaSize): self
    {
        $this->areaSize = $areaSize;
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

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;
        return $this;
    }

}
