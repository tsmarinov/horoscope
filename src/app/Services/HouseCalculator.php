<?php

namespace App\Services;

/**
 * Pure PHP Placidus house system calculator.
 *
 * Ported from Swiss Ephemeris swehouse.c (Astrodienst AG).
 * Key functions: Asc1(), Asc2(), CalcH() Placidus branch.
 *
 * House numbering: 1–12 (H1 = Ascendant, H10 = Midheaven).
 * All angles are ecliptic longitudes in degrees [0, 360).
 */
class HouseCalculator
{
    private const VERY_SMALL = 1e-8;

    /**
     * Calculate Placidus houses.
     *
     * @param  float  $jdUT    Julian Day (Universal Time)
     * @param  float  $lat     Geographic latitude in decimal degrees (negative = south)
     * @param  float  $lng     Geographic longitude in decimal degrees (negative = west)
     * @return array{
     *     cusps:     float[],   // 12-element array, cusps[0] = H1 (Asc), cusps[9] = H10 (MC)
     *     ascendant: float,     // ecliptic longitude of Ascendant
     *     mc:        float,     // ecliptic longitude of Midheaven
     * }
     */
    public function calculate(float $jdUT, float $lat, float $lng): array
    {
        // Time-dependent obliquity of the ecliptic (Meeus eq. 22.2)
        $T   = ($jdUT - 2451545.0) / 36525.0;
        $eps = 23.4392911111
            - (46.8150  / 3600) * $T
            - (0.00059  / 3600) * $T * $T
            + (0.001813 / 3600) * $T * $T * $T;

        $sine = sin(deg2rad($eps));
        $cose = cos(deg2rad($eps));
        $tane = $sine / $cose;

        // Clamp latitude away from poles
        if (abs(abs($lat) - 90) < self::VERY_SMALL) {
            $lat = $lat < 0 ? -90 + self::VERY_SMALL : 90 - self::VERY_SMALL;
        }
        $tanfi = tan(deg2rad($lat));

        $ramc = $this->ramc($jdUT, $lng);

        $mc  = $this->calcMC($ramc, $cose, $sine);
        $asc = $this->asc1($ramc + 90, $lat, $sine, $cose);

        $cusps    = array_fill(0, 12, 0.0);
        $cusps[0] = $asc;                        // H1
        $cusps[9] = $mc;                         // H10
        $cusps[3] = $this->norm($mc + 180);      // H4 = IC
        $cusps[6] = $this->norm($asc + 180);     // H7 = DSC

        // a = max solar declination reachable at this latitude
        $a = rad2deg(asin($this->clamp($tanfi * $tane)));

        // Pole heights for 1/3 and 2/3 trisection (Swiss Ephemeris fh1, fh2)
        $fh1 = rad2deg(atan(sin(deg2rad($a / 3))       / $tane)); // H11, H3
        $fh2 = rad2deg(atan(sin(deg2rad($a * 2 / 3))   / $tane)); // H12, H2

        // Intermediate cusps — SE offsets and divisors
        $cusps[10] = $this->placidus($ramc, $lat, $tanfi, $sine, $cose, 30,  3.0,   $fh1); // H11
        $cusps[11] = $this->placidus($ramc, $lat, $tanfi, $sine, $cose, 60,  1.5,   $fh2); // H12
        $cusps[1]  = $this->placidus($ramc, $lat, $tanfi, $sine, $cose, 120, 1.5,   $fh2); // H2
        $cusps[2]  = $this->placidus($ramc, $lat, $tanfi, $sine, $cose, 150, 3.0,   $fh1); // H3

        // Opposites
        $cusps[4] = $this->norm($cusps[10] + 180); // H5
        $cusps[5] = $this->norm($cusps[11] + 180); // H6
        $cusps[7] = $this->norm($cusps[1]  + 180); // H8
        $cusps[8] = $this->norm($cusps[2]  + 180); // H9

        return [
            'cusps'     => $cusps,
            'ascendant' => $asc,
            'mc'        => $mc,
        ];
    }

    /**
     * Assign house numbers (1–12) to an array of planet longitudes.
     *
     * @param  float[] $cusps   12-element cusp array (index 0 = H1 ... index 11 = H12)
     * @param  float[] $lons    Planet ecliptic longitudes
     * @return int[]            House number (1-based) for each planet
     */
    public function assignHouses(array $cusps, array $lons): array
    {
        $houses = [];
        foreach ($lons as $lon) {
            $houses[] = $this->houseForLongitude($lon, $cusps);
        }
        return $houses;
    }

    // -----------------------------------------------------------------------
    // Swiss Ephemeris Asc2 / Asc1
    // -----------------------------------------------------------------------

    /**
     * Core trigonometric formula (Swiss Ephemeris Asc2).
     * Returns an ecliptic longitude in degrees, without full quadrant correction.
     *
     * @param float $x     Angle in degrees (RAMC-based)
     * @param float $f     Latitude or pole height in degrees
     * @param float $sine  sin(obliquity)
     * @param float $cose  cos(obliquity)
     */
    private function asc2(float $x, float $f, float $sine, float $cose): float
    {
        $sinx = sin(deg2rad($x));
        $ass  = -tan(deg2rad($f)) * $sine + $cose * cos(deg2rad($x));

        if (abs($sinx) < self::VERY_SMALL) $sinx = 0.0;
        if (abs($ass)  < self::VERY_SMALL) $ass  = 0.0;

        if ($ass == 0.0) {
            return $sinx < 0.0 ? -90.0 : 90.0;
        }

        $result = rad2deg(atan($sinx / $ass));
        if ($ass < 0.0) {
            $result += 180.0;
        }
        return $result;
    }

    /**
     * Quadrant-corrected ascendant/cusp (Swiss Ephemeris Asc1).
     * Calls Asc2 with appropriate sign flips per quadrant.
     *
     * @param float $x     Angle in degrees (e.g. RAMC + 90 for ASC)
     * @param float $f     Latitude or pole height in degrees
     * @param float $sine  sin(obliquity)
     * @param float $cose  cos(obliquity)
     */
    private function asc1(float $x, float $f, float $sine, float $cose): float
    {
        $x = $this->norm($x);

        if (abs(90 - $f) < self::VERY_SMALL) return 180.0;
        if (abs(90 + $f) < self::VERY_SMALL) return   0.0;

        $n = (int)($x / 90) + 1; // quadrant 1..4

        switch ($n) {
            case 1: $ass =         $this->asc2($x,          $f, $sine, $cose); break;
            case 2: $ass = 180.0 - $this->asc2(180.0 - $x, -$f, $sine, $cose); break;
            case 3: $ass = 180.0 + $this->asc2($x - 180.0, -$f, $sine, $cose); break;
            default: $ass = 360.0 - $this->asc2(360.0 - $x,  $f, $sine, $cose); break;
        }

        return $this->norm($ass);
    }

    // -----------------------------------------------------------------------
    // Placidus intermediate cusp iterator (Swiss Ephemeris CalcH Placidus branch)
    // -----------------------------------------------------------------------

    /**
     * One Placidus intermediate cusp via Swiss Ephemeris iteration.
     *
     * @param float $ramc     RAMC in degrees
     * @param float $lat      Geographic latitude in degrees
     * @param float $tanfi    tan(lat) — pre-computed
     * @param float $sine     sin(obliquity)
     * @param float $cose     cos(obliquity)
     * @param float $offset   RAMC offset: 30 / 60 / 120 / 150
     * @param float $divisor  Trisection divisor: 3 or 1.5
     * @param float $fh       Pole height seed (fh1 or fh2)
     */
    private function placidus(
        float $ramc,
        float $lat,
        float $tanfi,
        float $sine,
        float $cose,
        float $offset,
        float $divisor,
        float $fh,
    ): float {
        $rectasc = $this->norm($ramc + $offset);

        // Initial seed via pole height
        $tant = tan(asin($this->clamp($sine * sin(deg2rad($this->asc1($rectasc, $fh, $sine, $cose))))));

        if (abs($tant) < self::VERY_SMALL) {
            return $rectasc;
        }

        $f    = rad2deg(atan(sin(asin($this->clamp($tanfi * $tant)) / $divisor) / $tant));
        $cusp = $this->asc1($rectasc, $f, $sine, $cose);

        $prev = 0.0;
        for ($i = 0; $i < 100; $i++) {
            $tant = tan(asin($this->clamp($sine * sin(deg2rad($cusp)))));
            if (abs($tant) < self::VERY_SMALL) {
                return $rectasc;
            }

            $f    = rad2deg(atan(sin(asin($this->clamp($tanfi * $tant)) / $divisor) / $tant));
            $cusp = $this->asc1($rectasc, $f, $sine, $cose);

            if ($i > 0 && abs($this->angularDiff($cusp, $prev)) < 1e-6) {
                break;
            }
            $prev = $cusp;
        }

        return $this->norm($cusp);
    }

    // -----------------------------------------------------------------------
    // MC / RAMC
    // -----------------------------------------------------------------------

    /** Right Ascension of the Midheaven (RAMC) in degrees. */
    private function ramc(float $jdUT, float $lng): float
    {
        $T = ($jdUT - 2451545.0) / 36525.0;

        // Greenwich Mean Sidereal Time in degrees (Meeus eq. 12.4)
        $gmst = 280.46061837
            + 360.98564736629 * ($jdUT - 2451545.0)
            + 0.000387933 * $T * $T
            - ($T * $T * $T) / 38710000.0;

        return $this->norm($gmst + $lng);
    }

    /** Ecliptic longitude of the Midheaven (MC). */
    private function calcMC(float $ramcDeg, float $cose, float $sine): float
    {
        $ramc = deg2rad($ramcDeg);
        $lon  = atan2(sin($ramc), cos($ramc) * $cose);
        return $this->norm(rad2deg($lon));
    }

    // -----------------------------------------------------------------------
    // House assignment
    // -----------------------------------------------------------------------

    /** Find which house (1-based) a longitude falls in. */
    private function houseForLongitude(float $lon, array $cusps): int
    {
        // Sort cusps by ecliptic longitude for arc-based containment check
        $pairs = [];
        foreach ($cusps as $h => $cusp) {
            $pairs[] = ['lon' => $cusp, 'house' => $h + 1];
        }
        usort($pairs, fn ($a, $b) => $a['lon'] <=> $b['lon']);

        $count = count($pairs);
        for ($i = 0; $i < $count; $i++) {
            $next  = ($i + 1) % $count;
            $start = $pairs[$i]['lon'];
            $end   = $pairs[$next]['lon'];

            if ($this->longitudeInArc($lon, $start, $end)) {
                return $pairs[$i]['house'];
            }
        }

        return 1;
    }

    /** True if $lon is in the arc from $start to $end (going forward). */
    private function longitudeInArc(float $lon, float $start, float $end): bool
    {
        if ($end > $start) {
            return $lon >= $start && $lon < $end;
        }
        return $lon >= $start || $lon < $end;
    }

    // -----------------------------------------------------------------------
    // Math helpers
    // -----------------------------------------------------------------------

    /** Clamp to [-1, 1] for safe asin/acos. */
    private function clamp(float $v): float
    {
        return max(-1.0, min(1.0, $v));
    }

    /** Signed angular difference in (-180, 180]. */
    private function angularDiff(float $a, float $b): float
    {
        $d = fmod($a - $b, 360.0);
        if ($d > 180.0)  $d -= 360.0;
        if ($d <= -180.0) $d += 360.0;
        return $d;
    }

    /** Minimum angular distance between two longitudes [0, 180]. */
    private function angularDist(float $a, float $b): float
    {
        $d = abs($a - $b);
        return $d > 180 ? 360 - $d : $d;
    }

    /** Normalize degrees to [0, 360). */
    private function norm(float $deg): float
    {
        $deg = fmod($deg, 360);
        return $deg < 0 ? $deg + 360 : $deg;
    }

    // -----------------------------------------------------------------------
    // Public utility
    // -----------------------------------------------------------------------

    /**
     * Convert a birth datetime (UTC) to Julian Day Number.
     *
     * @param  int  $year   Full year (e.g. 1990)
     * @param  int  $month  1–12
     * @param  int  $day    1–31
     * @param  int  $hour   0–23 (UTC)
     * @param  int  $minute 0–59
     * @return float
     */
    public static function toJulianDay(int $year, int $month, int $day, int $hour = 12, int $minute = 0): float
    {
        // Meeus, Chapter 7
        if ($month <= 2) {
            $year--;
            $month += 12;
        }

        $A = (int) ($year / 100);
        $B = 2 - $A + (int) ($A / 4);

        $jd = (int) (365.25 * ($year + 4716))
            + (int) (30.6001 * ($month + 1))
            + $day + $B - 1524.5;

        $jd += ($hour + $minute / 60.0) / 24.0;

        return $jd;
    }
}
