<?php

namespace App\DataTransfer\Horoscope;

class ProgressedMoonDTO
{
    /**
     * @param  string[]  $notes
     */
    public function __construct(
        public readonly string $summaryLine,
        public readonly array $notes,
        public readonly int $signIndex,
        public readonly string $signName,
        public readonly ?int $house,
        public readonly float $longitude,
    ) {}
}
