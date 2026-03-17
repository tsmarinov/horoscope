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

    /**
     * Pick a text block for a specific profile using section-level variant assignment.
     * On first call for a (profile, section) pair, assigns a random variant that exists
     * in that section and persists it. All subsequent calls for any key in that section
     * use the same variant, falling back to variant 1 if the key doesn't have that variant.
     */
    public static function pickForProfile(
        string $key,
        string $section,
        string $language,
        ?string $gender,
        ?int $profileId
    ): ?self {
        if ($profileId === null) {
            return static::pick($key, $section, 1, $language, $gender);
        }

        $assignment = TextBlockAssignment::firstOrCreate(
            ['profile_id' => $profileId, 'section' => $section],
            ['variant' => static::randomVariant($section)]
        );

        return static::pick($key, $section, $assignment->variant, $language, $gender)
            ?? static::pick($key, $section, 1, $language, $gender);
    }

    /**
     * Pick a random variant number that exists anywhere in the given section.
     * Returns 1 if no variants found.
     */
    private static function randomVariant(string $section): int
    {
        $variants = static::where('section', $section)
            ->distinct()
            ->pluck('variant')
            ->toArray();

        if (empty($variants)) {
            return 1;
        }

        return $variants[array_rand($variants)];
    }
}
