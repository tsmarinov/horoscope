<?php

namespace App\DataTransfer\Horoscope;

class DayRulerDTO
{
    public function __construct(
        public readonly string $weekday,
        public readonly int $dayOfWeek,
        public readonly string $planet,
        public readonly int $body,
        public readonly string $color,
        public readonly string $gem,
        public readonly int $number,
    ) {}
}
