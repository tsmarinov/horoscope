<?php

namespace App\DataTransfer\Horoscope;

class RetrogradePlanetDTO
{
    public function __construct(
        public readonly int $body,
        public readonly string $name,
        public readonly int $signIndex,
        public readonly string $signName,
    ) {}
}
