<?php

namespace App\DataTransfer\Horoscope;

class MonthlyHoroscopeDTO
{
    /**
     * @param  PlanetPositionDTO[]    $positions        1st of month
     * @param  TransitAspectDTO[]     $transitNatalAspects  top 15, slow first
     * @param  RetrogradePlanetDTO[]  $retrogrades
     * @param  LunationDTO[]          $lunations        NM + FM
     * @param  KeyDateDTO[]           $keyDates
     * @param  AreaOfLifeDTO[]        $areasOfLife
     */
    public function __construct(
        public readonly string $monthStart,
        public readonly string $monthEnd,
        public readonly string $profileName,
        public readonly array $positions,
        public readonly array $transitNatalAspects,
        public readonly array $retrogrades,
        public readonly array $lunations,
        public readonly ?ProgressedMoonDTO $progressedMoon,
        public readonly array $keyDates,
        public readonly array $areasOfLife,
    ) {}
}
