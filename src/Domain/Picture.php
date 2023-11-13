<?php

declare(strict_types=1);

namespace App\Domain;

final class Picture
{
    public function __construct(
        private int $id,
        private String $url,
        private String $quality,
    ) {
    }

    /**
     * Checks if the picture is high resolution based on the quality attribute
     *
     * @return boolean
     */
    public function isHighResolution(): bool
    {
        return strtoupper($this->quality) === 'HD';
    }

    public function getId(): int
    {
        return $this->id;
    }
}
