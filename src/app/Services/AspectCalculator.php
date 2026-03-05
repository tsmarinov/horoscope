<?php

namespace App\Services;

use App\Contracts\HoroscopeSubject;
use App\Models\NatalChart;
use App\Models\PlanetaryPosition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AspectCalculator
{
    private array $aspectConfig;
    private array $rulerships;
    private float $sunOrb;
    private float $moonOrb;

    public function __construct()
    {
        $this->aspectConfig = config('astrology.aspects', []);
        $this->rulerships   = config('astrology.rulerships', []);
        $this->sunOrb       = config('astrology.sun_orb', 5.0);
        $this->moonOrb      = config('astrology.moon_orb', 4.0);
    }

    public function calculate(HoroscopeSubject $subject): NatalChart
    {
        // For persistable subjects (User, GuestSession, DemoProfile) load cached chart
        if ($subject instanceof Model && $subject->natalChart) {
            return $subject->natalChart;
        }

        $result = $this->computeChart($subject);

        // Persist for Model-based subjects only (not GuestSubject)
        if ($subject instanceof Model) {
            $chart = $subject->natalChart()->create([
                'chart_tier' => $subject->getChartTier(),
                'planets'    => $result['planets'],
                'aspects'    => $result['aspects'],
                'houses'     => null,
                'ascendant'  => null,
                'mc'         => null,
            ]);

            return $chart;
        }

        // GuestSubject — return unsaved NatalChart instance
        return new NatalChart([
            'chart_tier' => $subject->getChartTier(),
            'planets'    => $result['planets'],
            'aspects'    => $result['aspects'],
        ]);
    }

    private function computeChart(HoroscopeSubject $subject): array
    {
        $birthDate = $subject->getBirthDate();
        $tier      = $subject->getChartTier();

        /** @var Collection<int, PlanetaryPosition> $positions */
        $positions    = PlanetaryPosition::forDate($birthDate)->get();
        $timeFraction = $this->resolveTimeFraction($subject, $tier);

        $planets = $positions->map(function (PlanetaryPosition $pos) use ($timeFraction, $tier): array {
            $longitude = $pos->longitude;

            if ($tier >= 2 && $timeFraction !== null) {
                $longitude += $pos->speed * $timeFraction;
                $longitude  = $this->normalizeLongitude($longitude);
            }

            return [
                'body'          => $pos->body,
                'longitude'     => $longitude,
                'speed'         => $pos->speed,
                'is_retrograde' => $pos->is_retrograde,
                'sign'          => (int) floor($longitude / 30),
                'degree'        => fmod($longitude, 30),
            ];
        })->values()->all();

        return [
            'planets' => $planets,
            'aspects' => $this->calculateAspects($planets, includeMutualReception: true),
        ];
    }

    private function resolveTimeFraction(HoroscopeSubject $subject, int $tier): ?float
    {
        if ($tier < 2) {
            return null;
        }

        $birthTime = $subject->getBirthTime();
        if ($birthTime === null) {
            return null;
        }

        $city = $subject->getBirthCity();
        $timezone = $city?->timezone ?? 'UTC';

        // Parse birth datetime in local timezone, then convert to UTC
        $localDt = Carbon::parse($subject->getBirthDate() . ' ' . $birthTime, $timezone);
        $utcDt   = $localDt->utc();

        return ($utcDt->hour + $utcDt->minute / 60 + $utcDt->second / 3600) / 24;
    }

    /**
     * Returns a fixed orb limit when Sun or Moon is involved, or null to use per-aspect orb.
     * Priority: Sun > Moon > null (use aspect default).
     */
    private function resolveOrbLimit(int $bodyA, int $bodyB): ?float
    {
        if ($bodyA === 0 || $bodyB === 0) {
            return $this->sunOrb;
        }

        if ($bodyA === 1 || $bodyB === 1) {
            return $this->moonOrb;
        }

        return null;
    }

    private function normalizeLongitude(float $longitude): float
    {
        $longitude = fmod($longitude, 360);
        if ($longitude < 0) {
            $longitude += 360;
        }
        return $longitude;
    }

    private function calculateAspects(array $planets, bool $includeMutualReception = false): array
    {
        $aspects = [];
        $count   = count($planets);

        for ($i = 0; $i < $count - 1; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $planets[$i];
                $b = $planets[$j];

                $diff = abs($a['longitude'] - $b['longitude']);
                // Normalize to 0–180°
                if ($diff > 180) {
                    $diff = 360 - $diff;
                }

                $best     = null;
                $bestOrb  = PHP_FLOAT_MAX;
                $orbLimit    = $this->resolveOrbLimit($a['body'], $b['body']);
                $lilitInvolved = $a['body'] === 12 || $b['body'] === 12;

                foreach ($this->aspectConfig as $name => $def) {
                    // Lilith forms only conjunctions with planets (house cusps handled separately)
                    if ($lilitInvolved && $def['angle'] !== 0) {
                        continue;
                    }

                    $effectiveOrb = $orbLimit ?? $def['orb'];
                    $deviation = abs($diff - $def['angle']);
                    if ($deviation <= $effectiveOrb && $deviation < $bestOrb) {
                        $bestOrb = $deviation;
                        $best    = [
                            'body_a'   => $a['body'],
                            'body_b'   => $b['body'],
                            'aspect'   => $name,
                            'angle'    => $def['angle'],
                            'orb'      => round($deviation, 6),
                            'applying' => $a['speed'] > $b['speed'],
                        ];
                    }
                }

                if ($best !== null) {
                    $aspects[] = $best;
                }
            }
        }

        if ($includeMutualReception) {
            foreach ($this->detectMutualReceptions($planets) as $mr) {
                $aspects[] = $mr;
            }
        }

        return $aspects;
    }

    /**
     * Detects mutual reception pairs: planet A is in a sign ruled by B,
     * and planet B is in a sign ruled by A. Recorded as a weak conjunction.
     */
    private function detectMutualReceptions(array $planets): array
    {
        $receptions = [];
        $count      = count($planets);

        for ($i = 0; $i < $count - 1; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $planets[$i];
                $b = $planets[$j];

                $rulerOfASign = $this->rulerships[$a['sign']] ?? null;
                $rulerOfBSign = $this->rulerships[$b['sign']] ?? null;

                if ($rulerOfASign === $b['body'] && $rulerOfBSign === $a['body']) {
                    $receptions[] = [
                        'body_a'   => $a['body'],
                        'body_b'   => $b['body'],
                        'aspect'   => 'mutual_reception',
                        'angle'    => 0,
                        'orb'      => 0.0,
                        'applying' => false,
                    ];
                }
            }
        }

        return $receptions;
    }
}
