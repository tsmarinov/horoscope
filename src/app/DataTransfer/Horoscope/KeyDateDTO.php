<?php

namespace App\DataTransfer\Horoscope;

class KeyDateDTO
{
    public function __construct(
        public readonly string  $date,
        public readonly string  $label,
        public readonly int     $priority = 1,   // 0=lunation, 1=slow planet, 2=fast planet
        public readonly ?string $textKey  = null,
        public readonly string  $section  = 'transit_natal',
    ) {}
}
