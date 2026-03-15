<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int     $id
 * @property string  $key
 * @property string  $section
 * @property string  $language
 * @property ?string $gender
 * @property int     $variant
 * @property string  $text
 * @property string  $tone       positive|negative|neutral
 *
 * @method static Builder forKey(string $key, string $section, string $language = 'en', ?string $gender = null)
 * @method static Builder forSection(string $section, string $language = 'en', ?string $gender = null)
 */
class TextBlock extends Model
{
    protected $fillable = [
        'key',
        'section',
        'language',
        'gender',
        'variant',
        'text',
        'tone',
        'tokens_in',
        'tokens_out',
        'cost_usd',
    ];

    /**
     * Map profile gender value to query gender.
     * male/other -> 'male', female -> 'female', null -> null
     */
    public static function resolveGender(?string $profileGender): ?string
    {
        return match ($profileGender) {
            'female' => 'female',
            'male', 'other' => 'male',
            default => null,
        };
    }

    /** Fetch all variants for a specific block key. */
    public function scopeForKey(Builder $query, string $key, string $section, string $language = 'en', ?string $gender = null): Builder
    {
        return $query->where('key', $key)
                     ->where('section', $section)
                     ->where('language', $language)
                     ->where('gender', $gender);
    }

    /** Fetch all blocks for a section in a given language. */
    public function scopeForSection(Builder $query, string $section, string $language = 'en', ?string $gender = null): Builder
    {
        return $query->where('section', $section)
                     ->where('language', $language)
                     ->where('gender', $gender);
    }

    /**
     * Resolve {masculine|feminine} gender markers in text.
     * First position = masculine / neutral, second = feminine.
     * Returns text unchanged when gender is null or no markers present.
     */
    public static function applyGender(string $text, ?string $gender): string
    {
        if ($gender === null || ! str_contains($text, '{')) {
            return $text;
        }

        return preg_replace_callback(
            '/\{([^|{}]+)\|([^{}|]+)\}/',
            fn ($m) => $gender === 'female' ? $m[2] : $m[1],
            $text
        );
    }

    /**
     * Pick a specific variant for a block key with 3-step fallback:
     * 1. language + gender  (when gender is not null)
     * 2. language + NULL    (neutral text; gender markers resolved if gender set)
     * 3. 'en' + NULL        (English neutral fallback, when language != 'en')
     */
    public static function pick(string $key, string $section, int $variant, string $language = 'en', ?string $gender = null): ?self
    {
        $base = static::where('key', $key)
                       ->where('section', $section)
                       ->where('variant', $variant);

        // Step 1: exact language + gender (only when gender is not null)
        if ($gender !== null) {
            $found = (clone $base)->where('language', $language)->where('gender', $gender)->first();
            if ($found) {
                return $found;
            }
        }

        // Step 2: language + neutral (gender = NULL) — apply template markers if gender set
        $found = (clone $base)->where('language', $language)->whereNull('gender')->first();
        if ($found) {
            if ($gender !== null) {
                $found->text = static::applyGender($found->text, $gender);
            }
            return $found;
        }

        // Step 3: English neutral fallback (only when language != 'en')
        if ($language !== 'en') {
            $found = (clone $base)->where('language', 'en')->whereNull('gender')->first();
            if ($found && $gender !== null) {
                $found->text = static::applyGender($found->text, $gender);
            }
            return $found;
        }

        return null;
    }
}
