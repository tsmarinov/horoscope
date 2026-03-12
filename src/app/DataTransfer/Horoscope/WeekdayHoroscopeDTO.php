<?php

namespace App\DataTransfer\Horoscope;

/** Full "Days of the Week" page data. */
class WeekdayHoroscopeDTO
{
    public function __construct(
        public readonly string  $date,         // highlighted date (YYYY-MM-DD)
        public readonly string  $profileName,  // empty string if no profile
        /** @var WeekdayDTO[] indexed 1–7 (ISO) */
        public readonly array   $days,
        public readonly ?int    $natalVenusSign, // for clothing tip lookup
    ) {}
}
