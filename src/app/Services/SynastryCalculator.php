<?php

namespace App\Services;

use App\Models\NatalChart;
use App\Models\PlanetaryPosition;

/**
 * Calculates cross-chart aspects between two natal charts.
 *
 * Only A-planets × B-planets (no same-person pairs).
 * Uses the same orb rules as AspectCalculator (sun_orb / moon_orb overrides).
 */
class SynastryCalculator
{
    private array $aspectConfig;
    private float $sunOrb;
    private float $moonOrb;

    public function __construct()
    {
        $this->aspectConfig = config('astrology.aspects', []);
        $this->sunOrb       = config('astrology.sun_orb', 5.0);
        $this->moonOrb      = config('astrology.moon_orb', 4.0);
    }

    /**
     * Calculate cross-chart aspects.
     * Returns aspects sorted by significance (weight desc, orb asc).
     *
     * Each aspect: body_a (chart A), body_b (chart B), aspect, orb, sign_a, sign_b.
     */
    public function calculate(NatalChart $chartA, NatalChart $chartB): array
    {
        $planetsA = $chartA->planets ?? [];
        $planetsB = $chartB->planets ?? [];
        $aspects  = [];

        foreach ($planetsA as $pa) {
            // Skip Lilith — only meaningful within a natal, not cross-chart
            if ($pa['body'] === PlanetaryPosition::LILITH) {
                continue;
            }

            foreach ($planetsB as $pb) {
                if ($pb['body'] === PlanetaryPosition::LILITH) {
                    continue;
                }

                $diff = abs($pa['longitude'] - $pb['longitude']);
                if ($diff > 180) {
                    $diff = 360 - $diff;
                }

                $orbLimit = $this->resolveOrbLimit($pa['body'], $pb['body']);
                $best     = null;
                $bestOrb  = PHP_FLOAT_MAX;

                foreach ($this->aspectConfig as $name => $def) {
                    $effectiveOrb = $orbLimit ?? $def['orb'];
                    $deviation    = abs($diff - $def['angle']);
                    if ($deviation <= $effectiveOrb && $deviation < $bestOrb) {
                        $bestOrb = $deviation;
                        $best    = [
                            'body_a'  => $pa['body'],
                            'body_b'  => $pb['body'],
                            'aspect'  => $name,
                            'orb'     => round($deviation, 4),
                            'sign_a'  => $pa['sign'],
                            'sign_b'  => $pb['sign'],
                        ];
                    }
                }

                if ($best !== null) {
                    $aspects[] = $best;
                }
            }
        }

        usort($aspects, function (array $a, array $b): int {
            $wa = $this->aspectWeight($a['aspect']);
            $wb = $this->aspectWeight($b['aspect']);
            if ($wa !== $wb) {
                return $wb <=> $wa; // higher weight first
            }
            return $a['orb'] <=> $b['orb'];
        });

        return $aspects;
    }

    private function resolveOrbLimit(int $bodyA, int $bodyB): ?float
    {
        if ($bodyA === PlanetaryPosition::SUN || $bodyB === PlanetaryPosition::SUN) {
            return $this->sunOrb;
        }
        if ($bodyA === PlanetaryPosition::MOON || $bodyB === PlanetaryPosition::MOON) {
            return $this->moonOrb;
        }
        return null;
    }

    private function aspectWeight(string $aspect): int
    {
        return match ($aspect) {
            'conjunction'  => 5,
            'opposition'   => 4,
            'trine'        => 4,
            'square'       => 3,
            'sextile'      => 3,
            'quincunx'     => 2,
            'semi_sextile' => 1,
            default        => 0,
        };
    }
}
