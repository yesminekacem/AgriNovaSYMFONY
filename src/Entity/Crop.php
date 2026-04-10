<?php

namespace App\Entity;

use App\Repository\CropRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CropRepository::class)]
#[ORM\Table(name: 'crop')]
class Crop
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $cropId = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', nullable: true, length: 100)]
    private ?string $variety = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $plantingDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $expectedHarvestDate = null;

    #[ORM\Column(type: 'string', nullable: true, length: 50)]
    private ?string $growthStage = null;

    #[ORM\Column(type: 'decimal', nullable: true, precision: 8, scale: 2)]
    private ?float $areaSize = null;

    #[ORM\Column(type: 'string', nullable: true, length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'string', nullable: true, length: 255)]
    private ?string $imagePath = null;


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

    public function getVariety(): ?string
    {
        return $this->variety;
    }

    public function setVariety(?string $variety): self
    {
        $this->variety = $variety;
        return $this;
    }

    public function getPlantingDate(): ?\DateTimeInterface
    {
        return $this->plantingDate;
    }

    public function setPlantingDate(?\DateTimeInterface $plantingDate): self
    {
        $this->plantingDate = $plantingDate;
        return $this;
    }

    public function getExpectedHarvestDate(): ?\DateTimeInterface
    {
        return $this->expectedHarvestDate;
    }

    public function setExpectedHarvestDate(?\DateTimeInterface $expectedHarvestDate): self
    {
        $this->expectedHarvestDate = $expectedHarvestDate;
        return $this;
    }

    public function getGrowthStage(): ?string
    {
        return $this->growthStage;
    }

    public function setGrowthStage(?string $growthStage): self
    {
        $this->growthStage = $growthStage;
        return $this;
    }

    public function getAreaSize(): ?float
    {
        return $this->areaSize;
    }

    public function setAreaSize(?float $areaSize): self
    {
        $this->areaSize = $areaSize;
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
