<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int      $id
 * @property int      $natal_report_id
 * @property int      $position
 * @property string   $key
 * @property string   $section
 * @property int|null $text_block_id
 * @property int|null $ai_text_id
 * @property int|null $transition_ai_text_id
 */
class NatalReportSection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'natal_report_id',
        'position',
        'key',
        'section',
        'text_block_id',
        'ai_text_id',
        'transition_ai_text_id',
    ];

    public function natalReport(): BelongsTo
    {
        return $this->belongsTo(NatalReport::class);
    }

    public function textBlock(): BelongsTo
    {
        return $this->belongsTo(TextBlock::class);
    }

    public function aiText(): BelongsTo
    {
        return $this->belongsTo(AiText::class, 'ai_text_id');
    }

    public function transitionAiText(): BelongsTo
    {
        return $this->belongsTo(AiText::class, 'transition_ai_text_id');
    }
}
