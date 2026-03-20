<?php

namespace App\DataTransfer\Horoscope;

/** Data for a single weekday card. */
class WeekdayDTO
{
    public function __construct(
        public readonly int    $isoDayOfWeek,   // 1=Mon … 7=Sun
        public readonly string $name,           // 'Monday'
        public readonly string $ruler,          // 'Moon'
        public readonly int    $number,
        public readonly string $colors,
        public readonly string $hex,            // UI color hint
        public readonly string $gem,
        public readonly string $theme,
        public readonly string $description,    // 2-sentence text
        public readonly bool   $isToday,
        public readonly ?string $clothingTipKey, // TextBlock key, null if no profile/not today
    ) {}
}
