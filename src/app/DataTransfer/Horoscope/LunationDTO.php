<?php

namespace App\DataTransfer\Horoscope;

class LunationDTO
{
    public function __construct(
        public readonly string $date,
        public readonly string $type,      // 'new_moon' | 'full_moon'
        public readonly string $name,
        public readonly int    $signIndex,
        public readonly string $signName,
        public readonly float  $longitude,
        public readonly ?int   $house = null,
    ) {}
}
