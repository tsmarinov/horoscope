<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $key
 * @property string $section
 * @property string $language
 * @property int    $variant
 * @property string $text
 * @property string $tone       positive|negative|neutral
 *
 * @method static Builder forKey(string $key, string $section, string $language = 'en')
 * @method static Builder forSection(string $section, string $language = 'en')
 */
class TextBlock extends Model
{
    protected $fillable = [
        'key',
        'section',
        'language',
        'variant',
        'text',
        'tone',
        'tokens_in',
        'tokens_out',
        'cost_usd',
    ];

    /** Fetch all variants for a specific block key. */
    public function scopeForKey(Builder $query, string $key, string $section, string $language = 'en'): Builder
    {
        return $query->where('key', $key)
                     ->where('section', $section)
                     ->where('language', $language);
    }

    /** Fetch all blocks for a section in a given language. */
    public function scopeForSection(Builder $query, string $section, string $language = 'en'): Builder
    {
        return $query->where('section', $section)
                     ->where('language', $language);
    }

    /**
     * Pick a specific variant for a block key.
     * Returns null if the variant doesn't exist yet.
     */
    public static function pick(string $key, string $section, int $variant, string $language = 'en'): ?self
    {
        return static::where('key', $key)
                     ->where('section', $section)
                     ->where('language', $language)
                     ->where('variant', $variant)
                     ->first();
    }
}
