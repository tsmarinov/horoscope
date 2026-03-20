<?php

namespace App\Services\Horoscope\Shared;

use App\DataTransfer\Horoscope\AreaOfLifeDTO;

class AreasOfLifeScorer
{
    // ── Life categories — house indices (0-based: H1=0 ... H12=11) ────────
    public const CATEGORIES = [
        ['slug' => 'love',            'name' => 'Love',            'houses' => [4]],
        ['slug' => 'home',            'name' => 'Home',            'houses' => [3]],
        ['slug' => 'creativity',      'name' => 'Creativity',      'houses' => [4]],
        ['slug' => 'spirituality',    'name' => 'Spirituality',    'houses' => [8, 11]],
        ['slug' => 'health',          'name' => 'Health',          'houses' => [5, 0]],
        ['slug' => 'finance',         'name' => 'Finance',         'houses' => [1, 7]],
        ['slug' => 'travel',          'name' => 'Travel',          'houses' => [8, 2]],
        ['slug' => 'career',          'name' => 'Career',          'houses' => [9, 5]],
        ['slug' => 'personal_growth', 'name' => 'Personal Growth', 'houses' => [0, 9, 10]],
        ['slug' => 'communication',   'name' => 'Communication',   'houses' => [2]],
        ['slug' => 'contracts',       'name' => 'Contracts',       'houses' => [6, 2]],
    ];

    public const MAX_RATING = 5;

    // ── Sign rulers (traditional, body IDs) ──────────────────────────────
    public const SIGN_RULERS = [
         0 => 4,  // Aries   -> Mars
         1 => 3,  // Taurus  -> Venus
         2 => 2,  // Gemini  -> Mercury
         3 => 1,  // Cancer  -> Moon
         4 => 0,  // Leo     -> Sun
         5 => 2,  // Virgo   -> Mercury
         6 => 3,  // Libra   -> Venus
         7 => 4,  // Scorpio -> Mars
         8 => 5,  // Sagittarius -> Jupiter
         9 => 6,  // Capricorn   -> Saturn
        10 => 6,  // Aquarius    -> Saturn
        11 => 5,  // Pisces      -> Jupiter
    ];

    // ── Max orbs per aspect type (for orb-weighted scoring) ──────────────
    public const MAX_ORBS = [
        'conjunction'  => 8.0,
        'opposition'   => 8.0,
        'trine'        => 8.0,
        'square'       => 8.0,
        'sextile'      => 6.0,
        'quincunx'     => 5.0,
        'semi_sextile' => 3.0,
    ];

    // ── Aspect weights: positive = beneficial, negative = challenging ─────
    public const ASPECT_WEIGHTS = [
        'trine'        => +2,
        'sextile'      => +1,
        'conjunction'  => +1,
        'semi_sextile' =>  0,
        'quincunx'     => -1,
        'square'       => -2,
        'opposition'   => -2,
    ];

    // ── Rating thresholds (score100 → rating 1–MAX_RATING; 0 = wait) ─────
    private const THRESHOLDS = [
        ['min' => 75, 'rating' => 5],
        ['min' => 55, 'rating' => 4],
        ['min' => 42, 'rating' => 3],
        ['min' => 30, 'rating' => 2],
    ];

    /**
     * Build orb-weighted natal body scores from raw aspects + Rx bodies for ONE day.
     *
     * @param  array  $aspects   from AspectCalculator::transitToNatal
     * @param  int[]  $rxBodies  retrograde body IDs
     * @return array<int, float>  [natal_body_id => float score]
     */
    public function buildDayScores(array $aspects, array $rxBodies): array
    {
        $nbs = [];

        foreach ($aspects as $asp) {
            $baseW     = self::ASPECT_WEIGHTS[$asp['aspect']] ?? 0;
            $maxOrb    = self::MAX_ORBS[$asp['aspect']] ?? 8.0;
            $orbFactor = max(0.0, 1.0 - ($asp['orb'] / $maxOrb));
            $nbs[$asp['natal_body']] = ($nbs[$asp['natal_body']] ?? 0) + $baseW * $orbFactor;
        }

        foreach ($rxBodies as $body) {
            $nbs[$body] = ($nbs[$body] ?? 0) - 1;
        }

        return $nbs;
    }

    /**
     * Score areas of life from pre-built natal body scores.
     * Used for both single-day and averaged multi-day scoring.
     *
     * @param  array<int, float>  $natalBodyScores  [natal_body_id => float]
     * @param  float[]            $houseCusps        12 cusp longitudes (0-based)
     * @return AreaOfLifeDTO[]
     */
    public function score(array $natalBodyScores, array $houseCusps): array
    {
        $result = [];

        foreach (self::CATEGORIES as $cat) {
            $score      = 0.0;
            $rulerCount = 0;

            foreach ($cat['houses'] as $hIdx) {
                $cuspLon = $houseCusps[$hIdx] ?? null;
                if ($cuspLon === null) {
                    continue;
                }
                $signIdx   = (int) floor(fmod($cuspLon, 360) / 30);
                $rulerBody = self::SIGN_RULERS[$signIdx] ?? null;
                if ($rulerBody !== null) {
                    $score += $natalBodyScores[$rulerBody] ?? 0;
                    $rulerCount++;
                }
            }

            if ($rulerCount > 1) {
                $score = $score / $rulerCount;
            }

            // Normalize: max_score = 4.0
            $score100 = max(0, min(100, (int) round(50.0 + ($score / 4.0) * 50.0)));

            // Map to numeric rating (0 = wait)
            $rating = 0;
            foreach (self::THRESHOLDS as $t) {
                if ($score100 >= $t['min']) {
                    $rating = $t['rating'];
                    break;
                }
            }

            $result[] = new AreaOfLifeDTO(
                slug:      $cat['slug'],
                name:      __('areas.' . $cat['slug']),
                score100:  $score100,
                rating:    $rating,
                maxRating: self::MAX_RATING,
            );
        }

        return $result;
    }
}
