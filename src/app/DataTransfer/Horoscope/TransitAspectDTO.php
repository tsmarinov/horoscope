<?php

namespace App\DataTransfer\Horoscope;

class TransitAspectDTO
{
    public function __construct(
        public readonly int $transitBody,
        public readonly string $transitName,
        public readonly int $natalBody,
        public readonly string $natalName,
        public readonly string $aspect,
        public readonly float $orb,
        public readonly ?string $peakDate = null,
        public readonly int $activeDays = 1,
    ) {}
}
