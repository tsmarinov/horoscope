# AGENTS.md — app/

## Overview
Laravel application layer — controllers, models, services, providers.

## Structure

### Http/Controllers/
- `Controller.php` — abstract base class; all controllers extend this
- Planned: `AuthController`, `HoroscopeController`, `NatalChartController`, `SynastryController`, `LunarCalendarController`, `ProfileController`

### Models/
- `User.php` — standard Laravel auth model; will be extended with `birth_date`, `birth_time`, `birth_city_id`, `chart_tier`
- `City.php` — world cities (5000+ pop); uses `astrotomic/laravel-translatable` with `$translatedAttributes = ['name']`; scopes: `search($term, $locale)`, `byCountry($code)`
- `CityTranslation.php` — translation rows for City; no timestamps; `$fillable = ['locale', 'name']`
- `DemoProfile.php` — public SEO profiles; implements HoroscopeSubject; route key = `slug`
- `PlanetaryPosition.php` — ephemeris data; composite PK `[date, body]`; no timestamps; body constants `SUN=0`…`LILITH=12`; scopes: `forDate()`, `forBody()`, `retrograde()`

### Contracts/ (app/Contracts/)
- `HoroscopeSubject.php` — interface implemented by User, DemoProfile, GuestSubject

### Support/ (app/Support/)
- `GuestSubject.php` — implements HoroscopeSubject from session data; `GuestSubject::fromSession()` factory

### Services/ (does not exist yet — must be created)
All business logic lives here. Do not put astrological calculations in controllers.

Conventions:
- One class per file
- Inject dependencies via constructor
- Pure calculation classes (no DB) should be `final` or `readonly` where appropriate

### Providers/
- `AppServiceProvider.php` — standard; add service container bindings here as needed

## Rules for Agents
- Controllers are thin — take request, call Service, return response
- No aspect calculation or text assembly logic inside controllers
- API controllers return `response()->json()`; Web controllers return `view()`

---

## Architecture Rules — Horoscope Layer

These rules apply to every horoscope service, DTO, Artisan command, and future frontend function.
Violations will require a refactor — get it right the first time.

### 1. Services return pure data — no display text

**Services and DTOs must never contain:**
- Emojis (❤️ 🔮 ★ ⚠ etc.)
- Star-rating strings (`★★★☆☆`, `⚠ wait`)
- Hardcoded display names in any language
- Cyrillic or any non-ASCII human-readable text

**Services and DTOs must use:**
- Slugs for categorical values: `'love'`, `'new_moon'`, `'waxing_crescent'`, `'trine'`
- Numeric ratings: `rating: int` (0 = wait, 1–maxRating = stars) + `maxRating: int`
- Laravel `__('file.key')` for any name/label that appears in a DTO (e.g. `__('areas.love')`)

### 2. Commands and frontend own all display mapping

Every Artisan pseudo-browser command (and every future frontend component) must:

```php
// Emoji maps — owned by the command, not the service
private const AREA_EMOJIS = [
    'love'          => '❤️',
    'home'          => '🏠',
    'creativity'    => '🎨',
    'spirituality'  => '🔮',
    'health'        => '💚',
    'finance'       => '💰',
    'travel'        => '✈️',
    'career'        => '💼',
    'personal_growth' => '🌱',
    'communication' => '💬',
    'contracts'     => '📝',
];

private const MOON_PHASE_EMOJIS = [
    'new_moon'        => '🌑',
    'waxing_crescent' => '🌒',
    'first_quarter'   => '🌓',
    'waxing_gibbous'  => '🌔',
    'full_moon'       => '🌕',
    'waning_gibbous'  => '🌖',
    'last_quarter'    => '🌗',
    'waning_crescent' => '🌘',
];

private const LUNATION_EMOJIS = [
    'new_moon'  => '🌑',
    'full_moon' => '🌕',
];

private const RULER_GLYPHS = [
    'Sun'     => '☉',  'Moon'    => '☽',  'Mercury' => '☿',
    'Venus'   => '♀',  'Mars'    => '♂',  'Jupiter' => '♃',
    'Saturn'  => '♄',
];

// Star rating renderer — one canonical implementation per command/component
private function ratingDisplay(int $rating, int $maxRating): string
{
    if ($rating === 0) {
        return '⚠ wait  ';
    }
    return str_repeat('★', $rating) . str_repeat('☆', $maxRating - $rating) . '  ';
}
```

### 3. Internationalisation — lang files + `__()`

- All user-visible strings live in `lang/{locale}/` files, never hardcoded in services or DTOs
- Current lang files: `lang/en/areas.php`, `lang/en/lunar.php`, `lang/en/weekdays.php`, `lang/en/ui.php`
- Use `__('areas.love')`, `__('lunar.phases.new_moon')`, `__('weekdays.1.name')` etc.
- Error messages, placeholders, and warnings in Artisan commands may remain in English inline
- When adding a new category/phase/weekday property: add to the lang file first, then reference via `__()`

### 4. AI synthesis prompts must include a language instruction

Every call to `AiProvider::generate()` must include a language directive in the system prompt:

```php
$langNote = $language !== 'en' ? "Write in language code: {$language}." : 'Write in English.';
$system   = "{$langNote}\n\n" . $system;
```

The `$language` parameter must flow from the request/command down to every AI call.
Never hardcode `'en'` in `AiProvider::generate()` calls — always pass `$language`.

### 5. UI display principle — glyphs + words, never symbols alone

In every output (ASCII UI, Blade templates, API responses):

```
☉ Sun in ♓ Pisces H8   ✓
☉ ♓ H8                 ✗
```

Planet glyphs, sign glyphs, and house numbers must always be accompanied by their text names.
