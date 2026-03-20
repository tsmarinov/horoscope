<?php

namespace App\DataTransfer\Horoscope;

class PlanetPositionDTO
{
    public function __construct(
        public readonly int $body,
        public readonly string $name,
        public readonly float $longitude,
        public readonly int $signIndex,
        public readonly string $signName,
        public readonly float $degreeInSign,
        public readonly bool $isRetrograde,
        public readonly float $speed,
    ) {}
}
