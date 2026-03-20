<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $geonames_id
 * @property string $country_code
 * @property float  $lat
 * @property float  $lng
 * @property string $timezone
 * @property int    $population
 * @property string $name  // translated attribute via astrotomic
 *
 * @method static Builder search(string $query, string $locale = 'en')
 * @method static Builder byCountry(string $countryCode)
 */
class City extends Model implements TranslatableContract
{
    use Translatable;

    public array $translatedAttributes = ['name'];

    protected $fillable = [
        'geonames_id',
        'country_code',
        'lat',
        'lng',
        'timezone',
        'population',
    ];

    protected $casts = [
        'lat'        => 'float',
        'lng'        => 'float',
        'population' => 'integer',
    ];

    /**
     * Prefix search across city_translations for autocomplete.
     * Results ordered by population descending (major cities first).
     */
    public function scopeSearch(Builder $query, string $term, string $locale = 'en'): Builder
    {
        return $query
            ->join('city_translations', function ($join) use ($locale) {
                $join->on('city_translations.city_id', '=', 'cities.id')
                     ->where('city_translations.locale', $locale);
            })
            ->where('city_translations.name', 'like', $term . '%')
            ->orderByDesc('cities.population')
            ->select('cities.*', 'city_translations.name as name');
    }

    public function scopeByCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }
}
