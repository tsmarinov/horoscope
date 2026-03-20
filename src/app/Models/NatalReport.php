<?php

namespace App\Models;

use App\Enums\ReportMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int        $id
 * @property int        $profile_id
 * @property int        $natal_chart_id
 * @property ReportMode $mode
 * @property string     $language
 * @property int|null   $introduction_ai_text_id
 * @property int|null   $house_lords_ai_text_id
 * @property int|null   $conclusion_ai_text_id
 */
class NatalReport extends Model
{
    protected $fillable = [
        'profile_id',
        'natal_chart_id',
        'mode',
        'language',
        'ai_tokens_in',
        'ai_tokens_out',
        'ai_cost_usd',
        'introduction_ai_text_id',
        'house_lords_ai_text_id',
        'conclusion_ai_text_id',
    ];

    protected function casts(): array
    {
        return [
            'mode' => ReportMode::class,
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function natalChart(): BelongsTo
    {
        return $this->belongsTo(NatalChart::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(NatalReportSection::class)->orderBy('position');
    }

    public function aiTexts(): HasMany
    {
        return $this->hasMany(AiText::class);
    }

    public function introductionAiText(): BelongsTo
    {
        return $this->belongsTo(AiText::class, 'introduction_ai_text_id');
    }

    public function houseLordsAiText(): BelongsTo
    {
        return $this->belongsTo(AiText::class, 'house_lords_ai_text_id');
    }

    public function conclusionAiText(): BelongsTo
    {
        return $this->belongsTo(AiText::class, 'conclusion_ai_text_id');
    }
}
