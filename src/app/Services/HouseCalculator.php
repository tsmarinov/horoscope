<?php

namespace App\Services;

/**
 * Pure PHP Placidus house system calculator.
 *
 * Computes the 12 house cusps, Ascendant (H1), and Midheaven (MC/H10)
 * from a Julian Day in Universal Time and geographic coordinates.
 *
 * References:
 *   - Jean Meeus, "Astronomical Algorithms", 2nd ed.
 *   - Placidus cusp iteration: Koch & Knappich (1988)
 *
 * House numbering: 1–12 (H1 = Ascendant, H10 = Midheaven).
 * All angles are ecliptic longitudes in degrees [0, 360).
 */
class HouseCalculator
{
    // Obliquity of the ecliptic (mean, J2000.0) — accurate to ±1° over 1920–2036
    private const OBLIQUITY = 23.4392911;

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
        $eps  = deg2rad(self::OBLIQUITY);
        $ramc = $this->ramc($jdUT, $lng);

        $mc  = $this->calcMC($ramc, $eps);
        $asc = $this->calcASC($ramc, $eps, $lat);

        $cusps    = array_fill(0, 12, 0.0);
        $cusps[0] = $asc;  // H1
        $cusps[9] = $mc;   // H10

        // H4 = MC + 180
        $cusps[3] = $this->norm($mc + 180);

        // H7 = ASC + 180
        $cusps[6] = $this->norm($asc + 180);

        // Intermediate cusps via Placidus iteration
        // H11 (1/3 diurnal arc), H12 (2/3 diurnal arc)
        $cusps[10] = $this->placidusIntermediate($ramc, $eps, $lat, offset: 30, factor: 1 / 3);
        $cusps[11] = $this->placidusIntermediate($ramc, $eps, $lat, offset: 60, factor: 2 / 3);

        // Nocturnal intermediate: these offsets compute H8 and H9 directly;
        // H2 and H3 are their opposites.
        $temp1 = $this->placidusIntermediate($ramc, $eps, $lat, offset: 120, factor: -1 / 3);
        $temp2 = $this->placidusIntermediate($ramc, $eps, $lat, offset: 150, factor: -2 / 3);

        $cusps[1] = $this->norm($temp1 + 180); // H2 = opposite of H8
        $cusps[2] = $this->norm($temp2 + 180); // H3 = opposite of H9

        // Opposites (H5, H6, H8, H9)
        $cusps[4] = $this->norm($cusps[10] + 180); // H5
        $cusps[5] = $this->norm($cusps[11] + 180); // H6
        $cusps[7] = $temp1;                        // H8
        $cusps[8] = $temp2;                        // H9

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
    // Private math
    // -----------------------------------------------------------------------

    /** Right Ascension of the Midheaven (RAMC) in degrees. */
    private function ramc(float $jdUT, float $lng): float
    {
        // Julian centuries from J2000.0
        $T = ($jdUT - 2451545.0) / 36525.0;

        // Greenwich Mean Sidereal Time in degrees (Meeus eq. 12.4)
        $gmst = 280.46061837
            + 360.98564736629 * ($jdUT - 2451545.0)
            + 0.000387933 * $T * $T
            - ($T * $T * $T) / 38710000.0;

        // RAMC = GMST + observer longitude (east positive)
        return $this->normDeg($gmst + $lng);
    }

    /** Ecliptic longitude of the Midheaven (MC). */
    private function calcMC(float $ramcDeg, float $epsRad): float
    {
        $ramc = deg2rad($ramcDeg);
        $lon  = atan2(sin($ramc), cos($ramc) * cos($epsRad));
        return $this->norm(rad2deg($lon));
    }

    /** Ecliptic longitude of the Ascendant. */
    private function calcASC(float $ramcDeg, float $epsRad, float $latDeg): float
    {
        $ramc = deg2rad($ramcDeg);
        $lat  = deg2rad($latDeg);

        $y   = -cos($ramc);
        $x   = sin($ramc) * cos($epsRad) + tan($lat) * sin($epsRad);
        $lon = atan2($y, $x);

        return $this->norm(rad2deg($lon));
    }

    /**
     * Placidus intermediate cusp via iteration.
     *
     * For diurnal cusps (H11, H12) the target RA is RAMC + offset + (1/3 or 2/3) × DSA.
     * For nocturnal cusps (H2, H3) offset is 120/150 and factor is negative (NSA-based).
     *
     * Converges in <10 iterations for all latitudes below ±66°.
     *
     * @param  float $ramc    RAMC in degrees
     * @param  float $eps     Obliquity in radians
     * @param  float $lat     Geographic latitude in degrees
     * @param  float $offset  Base RA offset from RAMC (30, 60, 120, 150)
     * @param  float $factor  Arc fraction (1/3, 2/3, -1/3, -2/3)
     */
    private function placidusIntermediate(
        float $ramc,
        float $eps,
        float $lat,
        float $offset,
        float $factor,
    ): float {
        $latRad = deg2rad($lat);

        // Initial guess: equal house interpolation from MC longitude
        $guess = $this->calcMC($ramc + $offset, $eps);

        for ($i = 0; $i < 20; $i++) {
            $dec = $this->eclipticToDecl($guess, $eps);

            // Semi-arc: arcsin(tan(dec) × tan(lat))
            $tanProd = tan($dec) * tan($latRad);

            // Circumpolar — clamp
            $tanProd = max(-1.0, min(1.0, $tanProd));
            $semiArc = rad2deg(asin($tanProd));

            $targetRA = $this->normDeg($ramc + $offset + $factor * $semiArc);
            $newLon   = $this->raToCusp($targetRA, $guess, $eps);

            if (abs($newLon - $guess) < 0.0001) {
                return $this->norm($newLon);
            }

            $guess = $newLon;
        }

        return $this->norm($guess);
    }

    /**
     * Convert ecliptic longitude to declination (radians).
     * Simplified formula assuming 0 latitude on ecliptic.
     */
    private function eclipticToDecl(float $lonDeg, float $epsRad): float
    {
        $lon = deg2rad($lonDeg);
        return asin(sin($epsRad) * sin($lon));
    }

    /**
     * Convert Right Ascension (degrees) to ecliptic longitude.
     * Inverse of ecliptic → equatorial, assuming zero ecliptic latitude.
     */
    private function raToCusp(float $raDeg, float $nearLon, float $epsRad): float
    {
        $ra  = deg2rad($raDeg);
        $lon = atan2(sin($ra) * cos($epsRad), cos($ra));
        $result = rad2deg($lon);

        // Resolve quadrant using nearLon
        $result = $this->norm($result);
        $nearLon = $this->norm($nearLon);

        // Pick the candidate closest to nearLon (avoid 180° flip from atan2)
        $alt = $this->norm($result + 180);
        if ($this->angularDist($alt, $nearLon) < $this->angularDist($result, $nearLon)) {
            $result = $alt;
        }

        return $result;
    }

    /** Minimum angular distance between two longitudes [0, 180]. */
    private function angularDist(float $a, float $b): float
    {
        $d = abs($a - $b);
        return $d > 180 ? 360 - $d : $d;
    }

    /** Find which house (1-based) a longitude falls in. */
    private function houseForLongitude(float $lon, array $cusps): int
    {
        // Sort by ecliptic longitude so we check arcs in zodiacal order,
        // not house-number order (which can create giant arcs in Placidus).
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

        return 1; // fallback
    }

    /** True if $lon is in the arc from $start to $end (going forward). */
    private function longitudeInArc(float $lon, float $start, float $end): bool
    {
        if ($end > $start) {
            return $lon >= $start && $lon < $end;
        }
        // Arc crosses 0°
        return $lon >= $start || $lon < $end;
    }

    /** Normalize degrees to [0, 360). */
    private function norm(float $deg): float
    {
        $deg = fmod($deg, 360);
        return $deg < 0 ? $deg + 360 : $deg;
    }

    /** Alias for norm() — same operation, separate name for clarity in RAMC context. */
    private function normDeg(float $deg): float
    {
        return $this->norm($deg);
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
