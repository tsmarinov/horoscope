<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Planetary body constants (match Swiss Ephemeris SE_* values):
 *  0 = Sun       1 = Moon      2 = Mercury   3 = Venus     4 = Mars
 *  5 = Jupiter   6 = Saturn    7 = Uranus    8 = Neptune   9 = Pluto
 * 10 = Chiron   11 = NNode (Mean)  12 = Lilith (Mean Apogee)
 *
 * South Node = NNode + 180° — calculated on the fly, not stored.
 *
 * @property string $date
 * @property int    $body
 * @property float  $longitude
 * @property float  $speed
 * @property bool   $is_retrograde
 *
 * @method static Builder forDate(string $date)
 * @method static Builder forBody(int $body)
 * @method static Builder retrograde(int $body)
 */
class PlanetaryPosition extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $primaryKey = ['date', 'body'];
    protected $keyType = 'string';

    protected $fillable = [
        'date',
        'body',
        'longitude',
        'speed',
        'is_retrograde',
    ];

    protected $casts = [
        'body'         => 'integer',
        'longitude'    => 'float',
        'speed'        => 'float',
        'is_retrograde' => 'boolean',
    ];

    // Zodiac sign names indexed by sign number (0=Aries … 11=Pisces)
    public const SIGN_NAMES = [
        0 => 'Aries', 1 => 'Taurus',  2 => 'Gemini',      3 => 'Cancer',
        4 => 'Leo',   5 => 'Virgo',   6 => 'Libra',        7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
    ];

    // Planetary body names indexed by body constant
    public const BODY_NAMES = [
        0  => 'Sun',     1  => 'Moon',    2  => 'Mercury', 3  => 'Venus',
        4  => 'Mars',    5  => 'Jupiter', 6  => 'Saturn',  7  => 'Uranus',
        8  => 'Neptune', 9  => 'Pluto',   10 => 'Chiron',  11 => 'NNode',
        12 => 'Lilith',
    ];

    // Body constants
    public const SUN     = 0;
    public const MOON    = 1;
    public const MERCURY = 2;
    public const VENUS   = 3;
    public const MARS    = 4;
    public const JUPITER = 5;
    public const SATURN  = 6;
    public const URANUS  = 7;
    public const NEPTUNE = 8;
    public const PLUTO   = 9;
    public const CHIRON  = 10;
    public const NNODE   = 11;
    public const LILITH  = 12;

    /** All bodies for a given date — most common query pattern. */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('date', $date);
    }

    /** All dates for a given body — used in transit calculations. */
    public function scopeForBody(Builder $query, int $body): Builder
    {
        return $query->where('body', $body);
    }

    /** Retrograde periods for a given body. */
    public function scopeRetrograde(Builder $query, int $body): Builder
    {
        return $query->where('body', $body)->where('is_retrograde', true);
    }
}
