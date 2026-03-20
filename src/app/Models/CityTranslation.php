<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $city_id
 * @property string $locale
 * @property string $name
 */
class CityTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'locale',
        'name',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
