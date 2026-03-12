<?php

namespace App\DataTransfer\Horoscope;

class TransitTransitDTO
{
    public function __construct(
        public readonly int $bodyA,
        public readonly string $nameA,
        public readonly int $bodyB,
        public readonly string $nameB,
        public readonly string $aspect,
        public readonly float $orb,
    ) {}
}
