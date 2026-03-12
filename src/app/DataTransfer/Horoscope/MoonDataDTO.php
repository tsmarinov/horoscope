<?php

namespace App\DataTransfer\Horoscope;

class MoonDataDTO
{
    public function __construct(
        public readonly int $lunarDay,
        public readonly string $phaseSlug,   // e.g. 'new_moon', 'waxing_crescent'
        public readonly string $phaseName,
        public readonly float $elongation,
        public readonly int $signIndex,
        public readonly string $signName,
    ) {}
}
