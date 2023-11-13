<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

final class Ad
{
    public function __construct(
        private int $id,
        private String $typology,
        private String $description,
        private array $pictures,
        private int $houseSize,
        private ?int $gardenSize = null,
        private ?int $score = null,
        private ?DateTimeImmutable $irrelevantSince = null,
    ) {
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function getTypology(): string
    {
        return $this->typology;
    }
    
    public function getDescription(): string
    {
        return $this->description;
    }
    
    public function getPictures(): array
    {
        return $this->pictures;
    }

    public function getHouseSize(): int
    {
        return $this->houseSize;
    }

    public function getGardenSize (): ?int
    {
        return $this->gardenSize;
    }

    public function getScore (): ?int
    {
        return $this->score;
    }
    
    public function setScore (int $score): void
    {
        $this->score = $score;
    }

    public function getIrrelevantSince(): ?DateTimeImmutable
    {
        return $this->irrelevantSince;
    }

    public function setIrrelevantSince(DateTimeImmutable $date): void
    {
        $this->irrelevantSince = $date;
    }
}
