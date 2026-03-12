<?php

namespace App\DataTransfer\Horoscope;

class SolarReturnDTO
{
    /**
     * @param  PlanetPositionDTO[]    $solarPlanets
     * @param  float[]                $solarHouses
     * @param  TransitAspectDTO[]     $solarNatalAspects
     * @param  array                  $progressedMoon
     * @param  array                  $progressedSun
     * @param  SolarArcDirectionDTO[] $solarArcDirections
     * @param  LunationDTO[]          $lunations
     * @param  QuarterDTO[]           $quarters
     * @param  RetrogradePlanetDTO[]  $retrogrades
     */
    public function __construct(
        public readonly int    $year,
        public readonly string $profileName,
        public readonly string $solarReturnDatetime,
        public readonly string $solarReturnUtcIso,
        public readonly string $cityName,
        public readonly float  $solarAscLon,
        public readonly int    $solarAscSignIndex,
        public readonly string $solarAscSignName,
        public readonly float  $solarMcLon,
        public readonly int    $solarMcSignIndex,
        public readonly string $solarMcSignName,
        public readonly array  $solarPlanets,
        public readonly array  $solarHouses,
        public readonly array  $solarNatalAspects,
        public readonly array  $progressedMoon,
        public readonly array  $progressedSun,
        public readonly array  $solarArcDirections,
        public readonly array  $lunations,
        public readonly array  $quarters,
        public readonly array  $retrogrades,
        public readonly string $leadingTheme,
    ) {}
}
