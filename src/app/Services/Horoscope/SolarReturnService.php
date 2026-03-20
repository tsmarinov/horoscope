<?php

namespace App\Services\Horoscope;

use App\DataTransfer\Horoscope\LunationDTO;
use App\DataTransfer\Horoscope\PlanetPositionDTO;
use App\DataTransfer\Horoscope\QuarterDTO;
use App\DataTransfer\Horoscope\RetrogradePlanetDTO;
use App\DataTransfer\Horoscope\SolarArcDirectionDTO;
use App\DataTransfer\Horoscope\SolarReturnDTO;
use App\DataTransfer\Horoscope\TransitAspectDTO;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Services\AspectCalculator;
use App\Services\HouseCalculator;
use App\Services\Horoscope\Shared\LunationDetector;
use App\Services\SolarReturnCalculator;
use Carbon\Carbon;

class SolarReturnService
{
    public function __construct(
        private readonly SolarReturnCalculator $calculator,
        private readonly HouseCalculator       $houses,
        private readonly AspectCalculator      $aspects,
        private readonly LunationDetector      $lunationDetector,
    ) {}

    public function build(Profile $profile, int $year): SolarReturnDTO
    {
        // a) Natal data
        $natalChart   = $profile->natalChart;
        $natalPlanets = $natalChart?->planets ?? [];
        $houseCusps   = $natalChart?->houses ?? [];

        // b) Find natal Sun longitude
        $natalSunLon = 0.0;
        foreach ($natalPlanets as $np) {
            if ((int) ($np['body'] ?? -1) === PlanetaryPosition::SUN) {
                $natalSunLon = (float) ($np['longitude'] ?? 0);
                break;
            }
        }

        // c) Find solar return moment
        $birthDate  = $profile->birth_date ? Carbon::parse($profile->birth_date) : Carbon::now();
        $sr = $this->calculator->find($natalSunLon, $birthDate->month, $birthDate->day, $year);

        // d) Load planetary positions for solar return date
        $srDate       = $sr['datetime']->toDateString();
        $srPositions  = PlanetaryPosition::forDate($srDate)->orderBy('body')->get();

        $solarPlanetsRaw = $srPositions->map(fn ($p) => [
            'body'          => $p->body,
            'longitude'     => $p->longitude,
            'speed'         => $p->speed,
            'sign'          => (int) floor($p->longitude / 30),
            'is_retrograde' => $p->is_retrograde,
        ])->values()->all();

        // e) Calculate solar return houses — solar_return_city overrides birth city
        $city = $profile->solarReturnCity ?? $profile->birthCity;
        if (! $city) {
            throw new \RuntimeException("Profile #{$profile->id} has no birth city.");
        }
        $lat = $city->lat;
        $lng = $city->lng;

        $srHousesResult = $this->houses->calculate($sr['jd'], $lat, $lng);
        $srCusps = $srHousesResult['cusps'];

        // f) Solar ASC and MC
        $solarAscLon = $srCusps[0];
        $solarMcLon  = $srCusps[9];

        $solarAscSignIndex = (int) floor($solarAscLon / 30);
        $solarAscSignName  = PlanetaryPosition::SIGN_NAMES[$solarAscSignIndex] ?? '';
        $solarMcSignIndex  = (int) floor($solarMcLon / 30);
        $solarMcSignName   = PlanetaryPosition::SIGN_NAMES[$solarMcSignIndex] ?? '';

        // g) Solar-to-natal aspects (top 8 by orb)
        $solarNatalRaw = ! empty($natalPlanets)
            ? $this->aspects->transitToNatal($solarPlanetsRaw, $natalPlanets)
            : [];

        $solarNatalAspects = array_map(
            fn ($asp) => new TransitAspectDTO(
                transitBody: $asp['transit_body'],
                transitName: PlanetaryPosition::BODY_NAMES[$asp['transit_body']] ?? '',
                natalBody:   $asp['natal_body'],
                natalName:   PlanetaryPosition::BODY_NAMES[$asp['natal_body']] ?? '',
                aspect:      $asp['aspect'],
                orb:         $asp['orb'],
            ),
            array_slice($solarNatalRaw, 0, 8),
        );

        // h) Progressed Moon and Sun (secondary progressions)
        $birthYear     = $birthDate->year;
        $age           = $year - $birthYear;
        $progressedDate = $birthDate->copy()->addDays($age)->toDateString();

        $progPositions = PlanetaryPosition::forDate($progressedDate)->get()->keyBy('body');

        $progressedMoon = $this->buildProgressedBody(
            $progPositions->get(PlanetaryPosition::MOON),
            $houseCusps,
        );
        $progressedSun = $this->buildProgressedBody(
            $progPositions->get(PlanetaryPosition::SUN),
            $houseCusps,
        );

        // i) Solar Arc Directions
        $natalSunLonForArc = $natalSunLon;
        $progSunLon        = $progPositions->get(PlanetaryPosition::SUN)?->longitude ?? $natalSunLon;
        $solarArc          = fmod($progSunLon - $natalSunLonForArc + 720, 360);

        $solarArcDirections = $this->computeSolarArcDirections($natalPlanets, $solarArc);

        // j) Lunations for the year (all), then filter to notable ones
        $allLunations = $this->detectYearlyLunations($year, $natalPlanets, $houseCusps);
        $natalLons = array_map(fn ($np) => (float) ($np['longitude'] ?? 0), $natalPlanets);
        $lunations = array_values(array_filter($allLunations, function (LunationDTO $lun) use ($natalLons, $year) {
            $lon = $lun->longitude;

            // Eclipse: lunation within 12° of North Node on that date
            $nnRow = PlanetaryPosition::forDate($lun->date)->where('body', PlanetaryPosition::NNODE)->first();
            if ($nnRow) {
                $diff = abs(fmod(abs($lon - $nnRow->longitude), 360));
                if ($diff > 180) {
                    $diff = 360 - $diff;
                }
                if ($diff <= 12.0) {
                    return true;
                }
            }

            // Natal hit: within 8° of any natal planet longitude
            foreach ($natalLons as $nLon) {
                $diff = abs(fmod(abs($lon - $nLon), 360));
                if ($diff > 180) {
                    $diff = 360 - $diff;
                }
                if ($diff <= 8.0) {
                    return true;
                }
            }

            return false;
        }));

        // k) Quarterly summary
        $quarters = $this->buildQuarters($year, $natalPlanets, $lunations);

        // l) Retrogrades for the year
        $retrogrades = $this->detectYearlyRetrogrades($year);

        // Build planet position DTOs
        $solarPlanetDTOs = $srPositions->map(fn ($p) => new PlanetPositionDTO(
            body:         $p->body,
            name:         PlanetaryPosition::BODY_NAMES[$p->body] ?? '',
            longitude:    $p->longitude,
            signIndex:    (int) floor($p->longitude / 30),
            signName:     PlanetaryPosition::SIGN_NAMES[(int) floor($p->longitude / 30)] ?? '',
            degreeInSign: fmod($p->longitude, 30),
            isRetrograde: $p->is_retrograde,
            speed:        $p->speed,
        ))->all();

        // Profile name
        $profileName = $profile->name ?? $profile->user?->name ?? 'Profile #' . $profile->id;

        // City name
        $cityName = $city->name;

        // Solar return datetime formatted
        $srDatetimeStr = $sr['datetime']->format('j M Y') . " \u{00B7} " . $sr['datetime']->format('H:i') . ' UTC';

        return new SolarReturnDTO(
            year:                $year,
            profileName:         $profileName,
            solarReturnDatetime: $srDatetimeStr,
            solarReturnUtcIso:   $sr['datetime']->toIso8601String(),
            cityName:            $cityName,
            solarAscLon:         $solarAscLon,
            solarAscSignIndex:   $solarAscSignIndex,
            solarAscSignName:    $solarAscSignName,
            solarMcLon:          $solarMcLon,
            solarMcSignIndex:    $solarMcSignIndex,
            solarMcSignName:     $solarMcSignName,
            solarPlanets:        $solarPlanetDTOs,
            solarHouses:         $srCusps,
            solarNatalAspects:   $solarNatalAspects,
            progressedMoon:      $progressedMoon,
            progressedSun:       $progressedSun,
            solarArcDirections:  $solarArcDirections,
            lunations:           $lunations,
            quarters:            $quarters,
            retrogrades:         $retrogrades,
            leadingTheme:        '',
        );
    }

    /**
     * Build progressed body data array.
     *
     * @return array{sign: int, signName: string, houseIndex: int|null, longitude: float}
     */
    private function buildProgressedBody(?PlanetaryPosition $position, array $houseCusps): array
    {
        if ($position === null) {
            return ['sign' => 0, 'signName' => 'Aries', 'houseIndex' => null, 'longitude' => 0.0];
        }

        $lon       = $position->longitude;
        $signIndex = (int) floor($lon / 30);
        $signName  = PlanetaryPosition::SIGN_NAMES[$signIndex] ?? '';
        $house     = ! empty($houseCusps) ? $this->findHouse($lon, $houseCusps) : null;

        return [
            'sign'       => $signIndex,
            'signName'   => $signName,
            'houseIndex' => $house,
            'longitude'  => $lon,
        ];
    }

    /**
     * Compute solar arc directions against natal chart.
     *
     * @return SolarArcDirectionDTO[]
     */
    private function computeSolarArcDirections(array $natalPlanets, float $solarArc): array
    {
        $aspectAngles = [
            'conjunction' => [0, 1.5],
            'opposition'  => [180, 1.5],
            'trine'       => [120, 1.5],
            'square'      => [90, 1.5],
            'sextile'     => [60, 1.0],
            'quincunx'    => [150, 1.0],
        ];

        $directions = [];

        foreach ($natalPlanets as $directed) {
            $directedBody = (int) ($directed['body'] ?? -1);
            $directedLon  = (float) ($directed['longitude'] ?? 0);
            $directedNew  = fmod($directedLon + $solarArc + 720, 360);

            foreach ($natalPlanets as $target) {
                $targetBody = (int) ($target['body'] ?? -1);
                $targetLon  = (float) ($target['longitude'] ?? 0);

                // Skip self
                if ($directedBody === $targetBody) {
                    continue;
                }

                $diff = abs($directedNew - $targetLon);
                if ($diff > 180) {
                    $diff = 360 - $diff;
                }

                foreach ($aspectAngles as $aspectName => [$angle, $maxOrb]) {
                    $orb = abs($diff - $angle);
                    if ($orb <= $maxOrb) {
                        $directions[] = [
                            'dto' => new SolarArcDirectionDTO(
                                directedBody:    $directedBody,
                                directedName:    PlanetaryPosition::BODY_NAMES[$directedBody] ?? '',
                                natalTargetBody: $targetBody,
                                natalTargetName: PlanetaryPosition::BODY_NAMES[$targetBody] ?? '',
                                aspect:          $aspectName,
                                orb:             round($orb, 2),
                            ),
                            'orb' => $orb,
                        ];
                    }
                }
            }
        }

        // Sort by orb, take max 5
        usort($directions, fn ($a, $b) => $a['orb'] <=> $b['orb']);

        return array_map(
            fn ($d) => $d['dto'],
            array_slice($directions, 0, 5),
        );
    }

    /**
     * Detect lunations (new + full moons) for the entire year.
     * Marks eclipses when lunation is within 15 degrees of North Node.
     *
     * @return LunationDTO[]
     */
    private function detectYearlyLunations(int $year, array $natalPlanets, array $houseCusps): array
    {
        $lunations = [];

        for ($month = 1; $month <= 12; $month++) {
            $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $end   = $start->copy()->endOfMonth();

            // Load all positions for this month
            $positions = PlanetaryPosition::whereBetween('date', [
                $start->toDateString(),
                $end->toDateString(),
            ])->orderBy('date')->orderBy('body')->get();

            // Group by date
            $dayPositions = [];
            foreach ($positions as $pos) {
                $dayPositions[$pos->date][] = $pos;
            }

            // Convert to keyed collections for LunationDetector
            $grouped = [];
            foreach ($dayPositions as $date => $posArray) {
                $grouped[$date] = collect($posArray);
            }

            $monthLunations = $this->lunationDetector->detect($grouped, 1);

            foreach ($monthLunations as $lun) {
                $lunations[] = $lun;
            }
        }

        return $lunations;
    }

    /**
     * Build quarterly summaries with slow-planet transit aspects, Rx events, and notable lunations.
     *
     * @param  array        $natalPlanets  raw natal planet arrays with 'body' and 'longitude'
     * @param  LunationDTO[] $filteredLunations
     * @return QuarterDTO[]
     */
    private function buildQuarters(int $year, array $natalPlanets, array $filteredLunations): array
    {
        $quarterLabels = [
            1 => __('ui.quarters.q1'),
            2 => __('ui.quarters.q2'),
            3 => __('ui.quarters.q3'),
            4 => __('ui.quarters.q4'),
        ];

        $slowBodies = [
            PlanetaryPosition::JUPITER,
            PlanetaryPosition::SATURN,
            PlanetaryPosition::URANUS,
            PlanetaryPosition::NEPTUNE,
            PlanetaryPosition::PLUTO,
        ];

        $aspectAngles = [
            'conjunction' => 0,
            'opposition'  => 180,
            'trine'       => 120,
            'square'      => 90,
            'sextile'     => 60,
        ];

        $aspectNames = [
            'conjunction' => 'Conjunction',
            'opposition'  => 'Opposition',
            'trine'       => 'Trine',
            'square'      => 'Square',
            'sextile'     => 'Sextile',
        ];

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar',  4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul',  8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        // Collect candidates per quarter: ['q' => int, 'orb' => float, 'tight' => bool, 'eclipse' => bool, 'item' => string]
        $candidates = [1 => [], 2 => [], 3 => [], 4 => []];

        // ── Slow planet transits to natal ─────────────────────────────────
        // Sample each month on the 15th; deduplicate same combination per quarter (keep lowest orb)
        $seenPerQuarter = [1 => [], 2 => [], 3 => [], 4 => []]; // 'key' => orb

        for ($m = 1; $m <= 12; $m++) {
            $q    = (int) ceil($m / 3);
            $date = sprintf('%04d-%02d-15', $year, $m);

            $positions = PlanetaryPosition::forDate($date)
                ->whereIn('body', $slowBodies)
                ->get()
                ->keyBy('body');

            foreach ($slowBodies as $body) {
                $pos = $positions->get($body);
                if (! $pos) {
                    continue;
                }

                $bodyName  = PlanetaryPosition::BODY_NAMES[$body] ?? '';
                $isRx      = $pos->is_retrograde;
                $transitLon = $pos->longitude;

                // Rx event: record once per quarter per body
                $rxKey = "rx_{$body}_{$q}";
                if ($isRx && ! isset($seenPerQuarter[$q][$rxKey])) {
                    $seenPerQuarter[$q][$rxKey] = 0;
                    $candidates[$q][] = [
                        'orb'     => 999,
                        'tight'   => false,
                        'eclipse' => false,
                        'item'    => $monthNames[$m] . ' · ' . $bodyName . ' Rx',
                    ];
                }

                // Aspects to natal planets
                foreach ($natalPlanets as $np) {
                    $nBody = (int) ($np['body'] ?? -1);
                    $nLon  = (float) ($np['longitude'] ?? 0);
                    $nName = PlanetaryPosition::BODY_NAMES[$nBody] ?? '';

                    $diff = abs(fmod(abs($transitLon - $nLon), 360));
                    if ($diff > 180) {
                        $diff = 360 - $diff;
                    }

                    foreach ($aspectAngles as $aspSlug => $angle) {
                        $orb = abs($diff - $angle);
                        if ($orb > 3.0) {
                            continue;
                        }

                        $dedupeKey = "{$body}_{$aspSlug}_{$nBody}";
                        if (
                            ! isset($seenPerQuarter[$q][$dedupeKey])
                            || $seenPerQuarter[$q][$dedupeKey] > $orb
                        ) {
                            $seenPerQuarter[$q][$dedupeKey] = $orb;

                            $aspName = $aspectNames[$aspSlug];
                            $candidates[$q][] = [
                                'orb'     => $orb,
                                'tight'   => $orb <= 1.0,
                                'eclipse' => false,
                                'item'    => $monthNames[$m] . ' · ' . $bodyName . ' ' . $aspName . ' natal ' . $nName,
                            ];
                        }
                    }
                }
            }
        }

        // ── Notable lunations ─────────────────────────────────────────────
        foreach ($filteredLunations as $lun) {
            $lunMonth = (int) Carbon::parse($lun->date)->month;
            $q        = (int) ceil($lunMonth / 3);
            $dayStr   = Carbon::parse($lun->date)->format('j M');
            $typeLabel = $lun->type === 'new_moon' ? 'New Moon' : 'Full Moon';

            // Detect eclipse: within 12° of North Node on that date
            $isEclipse = false;
            $nnRow = PlanetaryPosition::forDate($lun->date)->where('body', PlanetaryPosition::NNODE)->first();
            if ($nnRow) {
                $diff = abs(fmod(abs($lun->longitude - $nnRow->longitude), 360));
                if ($diff > 180) {
                    $diff = 360 - $diff;
                }
                $isEclipse = $diff <= 12.0;
            }

            $eclipseTag = $isEclipse ? ' (Eclipse)' : '';
            $candidates[$q][] = [
                'orb'     => $isEclipse ? -1 : 50,
                'tight'   => $isEclipse,
                'eclipse' => $isEclipse,
                'item'    => $dayStr . ' · ' . $typeLabel . ' ' . $lun->signName . $eclipseTag,
            ];
        }

        // ── Build final quarters ──────────────────────────────────────────
        $quarters = [];
        foreach ($quarterLabels as $q => $label) {
            $pool = $candidates[$q];

            // Deduplicate items by text (keep lowest orb)
            $deduped = [];
            foreach ($pool as $c) {
                $key = $c['item'];
                if (! isset($deduped[$key]) || $deduped[$key]['orb'] > $c['orb']) {
                    $deduped[$key] = $c;
                }
            }
            $pool = array_values($deduped);

            // Sort: eclipses first, then tight aspects (orb ≤1), then wider aspects, then Rx/lunations
            usort($pool, function ($a, $b) {
                $scoreA = $a['eclipse'] ? 0 : ($a['tight'] ? 1 : ($a['orb'] < 100 ? 2 : 3));
                $scoreB = $b['eclipse'] ? 0 : ($b['tight'] ? 1 : ($b['orb'] < 100 ? 2 : 3));
                if ($scoreA !== $scoreB) {
                    return $scoreA <=> $scoreB;
                }
                return $a['orb'] <=> $b['orb'];
            });

            $items = array_slice(array_column($pool, 'item'), 0, 4);

            $quarters[] = new QuarterDTO(
                quarter: $q,
                label:   $label,
                items:   $items,
            );
        }

        return $quarters;
    }

    /**
     * Detect planets that are retrograde at any point during the year.
     * Samples 4 dates across the year and collects unique retrograde bodies.
     *
     * @return RetrogradePlanetDTO[]
     */
    private function detectYearlyRetrogrades(int $year): array
    {
        $sampleDates = [
            "{$year}-01-01",
            "{$year}-04-01",
            "{$year}-07-01",
            "{$year}-10-01",
        ];

        $rxBodies = [];

        foreach ($sampleDates as $date) {
            $positions = PlanetaryPosition::forDate($date)
                ->where('is_retrograde', true)
                ->where('body', '>=', 2) // Mercury through Pluto
                ->where('body', '<=', 9)
                ->get();

            foreach ($positions as $pos) {
                if (! isset($rxBodies[$pos->body])) {
                    $signIndex = (int) floor($pos->longitude / 30);
                    $rxBodies[$pos->body] = new RetrogradePlanetDTO(
                        body:      $pos->body,
                        name:      PlanetaryPosition::BODY_NAMES[$pos->body] ?? '',
                        signIndex: $signIndex,
                        signName:  PlanetaryPosition::SIGN_NAMES[$signIndex] ?? '',
                    );
                }
            }
        }

        // Sort by body index
        ksort($rxBodies);

        return array_values($rxBodies);
    }

    /**
     * Find which house (1-based) a longitude falls into.
     */
    private function findHouse(float $longitude, array $houseCusps): ?int
    {
        if (count($houseCusps) < 12) {
            return null;
        }

        $lon = fmod($longitude + 360, 360);

        for ($h = 0; $h < 12; $h++) {
            $cusp     = fmod($houseCusps[$h] + 360, 360);
            $nextCusp = fmod($houseCusps[($h + 1) % 12] + 360, 360);

            if ($cusp <= $nextCusp) {
                if ($lon >= $cusp && $lon < $nextCusp) {
                    return $h + 1;
                }
            } else {
                if ($lon >= $cusp || $lon < $nextCusp) {
                    return $h + 1;
                }
            }
        }

        return 1;
    }
}
