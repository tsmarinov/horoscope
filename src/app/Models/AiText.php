<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $natal_report_id
 * @property string $type            introduction|section|transition|conclusion
 * @property string $text
 */
class AiText extends Model
{
    protected $fillable = [
        'natal_report_id',
        'type',
        'text',
        'tokens_in',
        'tokens_out',
        'cost_usd',
    ];

    public function natalReport(): BelongsTo
    {
        return $this->belongsTo(NatalReport::class);
    }
}
