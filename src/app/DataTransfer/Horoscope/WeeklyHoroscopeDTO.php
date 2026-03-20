<?php

namespace App\DataTransfer\Horoscope;

class WeeklyHoroscopeDTO
{
    /**
     * @param  PlanetPositionDTO[]    $positions        Monday positions
     * @param  TransitAspectDTO[]     $transitNatalAspects  top 7, slow first
     * @param  RetrogradePlanetDTO[]  $retrogrades
     * @param  KeyDateDTO[]           $keyDates
     * @param  AreaOfLifeDTO[]        $areasOfLife
     */
    public function __construct(
        public readonly string $weekStart,
        public readonly string $weekEnd,
        public readonly string $profileName,
        public readonly array $positions,
        public readonly array $transitNatalAspects,
        public readonly array $retrogrades,
        public readonly ?LunationDTO $lunation,
        public readonly array $keyDates,
        public readonly array $areasOfLife,
    ) {}
}
