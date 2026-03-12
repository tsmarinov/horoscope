<?php

namespace App\DataTransfer\Horoscope;

class KeyDateDTO
{
    public function __construct(
        public readonly string $date,
        public readonly string $label,
    ) {}
}
