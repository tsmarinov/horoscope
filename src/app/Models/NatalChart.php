<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int         $id
 * @property string      $subject_type
 * @property int         $subject_id
 * @property int         $chart_tier
 * @property array       $planets
 * @property array       $aspects
 * @property array|null  $houses
 * @property float|null  $ascendant
 * @property float|null  $mc
 * @property \Carbon\Carbon $calculated_at
 */
class NatalChart extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'chart_tier',
        'planets',
        'aspects',
        'houses',
        'ascendant',
        'mc',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'chart_tier'    => 'integer',
            'planets'       => 'array',
            'aspects'       => 'array',
            'houses'        => 'array',
            'ascendant'     => 'float',
            'mc'            => 'float',
            'calculated_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
