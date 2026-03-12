<?php

namespace App\DataTransfer\Horoscope;

class QuarterDTO
{
    /**
     * @param  string[]  $items
     */
    public function __construct(
        public readonly int    $quarter,
        public readonly string $label,
        public readonly array  $items,
    ) {}
}
