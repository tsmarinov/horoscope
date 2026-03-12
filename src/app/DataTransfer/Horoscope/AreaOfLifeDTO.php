<?php

namespace App\DataTransfer\Horoscope;

class AreaOfLifeDTO
{
    public function __construct(
        public readonly string $slug,       // e.g. 'love', 'personal_growth'
        public readonly string $name,
        public readonly int    $score100,
        public readonly int    $rating,     // 0 = wait, 1–maxRating = stars
        public readonly int    $maxRating,  // rating scale (e.g. 5)
    ) {}
}
