# AGENTS.md — app/

## Overview
Laravel 12 / PHP 8.2 horoscope platform — services, DTOs, commands, models.

---

## Implementation Status

| Section | Command | Service | Status |
|---------|---------|---------|--------|
| 4.1 Daily | `horoscope:ui-daily {profile} {--date=} {--simplified} {--ai}` | `DailyHoroscopeService` | ✅ done |
| 4.2 Weekly | `horoscope:ui-weekly {profile} {--date=} {--simplified} {--ai}` | `WeeklyHoroscopeService` | ✅ done |
| 4.3 Monthly | `horoscope:ui-monthly {profile} {--date=} {--simplified} {--ai}` | `MonthlyHoroscopeService` | ✅ done |
| 4.4 Solar Return | `horoscope:ui-solar {profile} {year?} {--simplified} {--ai}` | `SolarReturnService` | ✅ done |
| 4.6 Lunar Calendar | `horoscope:ui-lunar {profile} {--month=}` | — | ✅ done |
| 4.7 Weekday | `horoscope:ui-weekday {--date=}` | `WeekdayHoroscopeService` | ✅ done |
| 4.9 Natal Chart | `horoscope:ui-natal {profile} {--mode=organic}` | `ReportBuilder` | ✅ done |
| 4.10 Planet Positions | `horoscope:ui-planet-positions {--date=}` | `AspectCalculator` | ✅ done |
| 4.5 Key Dates | — | `KeyDatesBuilder` | ⏳ service only |
| 4.8 Retrograde Cal | — | — | ⏳ demo only |
| 4.11 Synastry | — | — | ⏳ demo only |
| 4.12 Composite | — | — | ⏳ demo only |
| 4.13 Transits | — | — | ⏳ demo only |

---

## Database Tables

| Table | Migration | Status | Notes |
|-------|-----------|--------|-------|
| `users` | `0001_01_01_000000` | ✅ migrated | standard Laravel auth |
| `cache`, `jobs` | `0001_01_01_000001/2` | ✅ migrated | standard Laravel |
| `planetary_positions` | `2026_03_04_123332` | ✅ migrated + data | 555,555 rows (1920–2036); composite PK `[date, body]` |
| `cities` | `2026_03_04_150449` | ✅ migrated + data | 68,047 rows (GeoNames 5000+ pop) |
| `text_blocks` | `2026_03_04_300000` | ✅ migrated | key/section/language/variant; stores pre-generated + AI cached texts |
| `guest_sessions` | `2026_03_04_200000` | ✅ migrated | anonymous users |
| `profiles` | `2026_03_04_200001` | ✅ migrated | unified: user / guest / demo; has `solar_return_city_id` (nullable FK) |
| `natal_charts` | `2026_03_04_200002` | ✅ migrated | JSON: planets, aspects, houses, ascendant, mc |
| `natal_reports` | `2026_03_05_100000` | ✅ migrated | cached AI-generated reports |
| `weekday_texts` | `2026_03_12_000001` | ✅ migrated | 7 rows/language; seeded for 'en'; CMS-editable |

### `weekday_texts` columns
`iso_day` (1=Mon…7=Sun), `language`, `name`, `colors`, `gem`, `theme`, `description`
UNIQUE(`iso_day`, `language`) — read by `WeekdayHoroscopeService` and `UiWeekday`.

### `profiles.solar_return_city_id`
Nullable FK to `cities`. When set, overrides `birth_city_id` for solar return calculation.
**No fallback** — if solar calculation is requested and no city is set, throws `RuntimeException`.

---

## Lang Files

```
lang/en/
  areas.php   — 11 area slugs → names (love, home, creativity, …)
  lunar.php   — 8 moon phase names + 2 lunation names (new_moon, full_moon)
  ui.php      — rating_wait, retrograde, retrograde_short, aspects[], rx_legend,
                 today_mark, no_rx, areas.title, lunar.*, weekday.footer,
                 natal.footer_links
```

**Deleted:** `lang/en/weekdays.php` — moved to `weekday_texts` DB table (CMS-editable, multi-language).

---

## Artisan Commands

### Pseudo-browser UI (development + layout testing)
| Command | Description |
|---------|-------------|
| `horoscope:ui-daily {profile}` | Daily horoscope; `--date=`, `--simplified`, `--ai` |
| `horoscope:ui-weekly {profile}` | Weekly horoscope; `--date=`, `--simplified`, `--ai` |
| `horoscope:ui-monthly {profile}` | Monthly horoscope; `--date=`, `--simplified`, `--ai` |
| `horoscope:ui-solar {profile} {year?}` | Solar return / yearly; `--simplified`, `--ai` (5 paragraphs) |
| `horoscope:ui-lunar {profile}` | Lunar calendar; `--month=` |
| `horoscope:ui-weekday` | Weekday page; `--date=` |
| `horoscope:ui-natal {profile}` | Natal chart; `--mode=organic\|simplified\|ai_l1\|ai_l1_haiku` |
| `horoscope:ui-planet-positions` | Public ephemeris; `--date=`; shows orbs (public page) |

### Text generation
| Command | Keys | Section | Notes |
|---------|------|---------|-------|
| `horoscope:generate-transit --type=transit` | ~474 | `transit` | transit-to-transit aspects |
| `horoscope:generate-transit --type=transit_natal` | ~1033 | `transit_natal` | transit-to-natal aspects |
| `horoscope:generate-transit --type=retrograde` | ~60 | `retrograde` | planet Rx in sign |
| `horoscope:generate-natal` | ~474 | `natal` | natal aspects |
| `horoscope:generate-natal-house-lords` | — | `natal_house_lords` | house lord descriptions |
| `horoscope:generate-natal-asc-lord` | ~1,728 | `natal_asc_lord` | ASC sign × lord sign × lord house |
| `horoscope:generate-lunar` | 60 | `lunar_day` | moon in sign (manually written) |
| All generate commands support `--short` | same key | `{section}_short` | haiku/simplified variant |
| All generate commands support `--variants=N` | — | — | dev=1, beta=3, prod=8 |

### Other
- `horoscope:import-cities` — GeoNames import (pending)
- `horoscope:deploy-import-sql` — import planetary_positions from SQL dump

---

## Services

### Core calculation
| Service | Purpose |
|---------|---------|
| `AspectCalculator` | `transitToTransit()`, `transitToNatal()`, `calculateAspects()`; reads config orbs |
| `HouseCalculator` | Placidus houses from JD + lat/lng; `toJulianDay()` |
| `SolarReturnCalculator` | Newton-Raphson: finds exact JD when Sun crosses natal longitude in target year |
| `VariantPicker` | Selects TextBlock variant (round-robin or random) |
| `ReportBuilder` | Builds natal reports in 4 modes: organic / simplified / ai_l1 / ai_l1_haiku |

### Horoscope services
| Service | Input | Output |
|---------|-------|--------|
| `DailyHoroscopeService` | `Profile`, date | `DailyHoroscopeDTO` |
| `WeeklyHoroscopeService` | `Profile`, date | `WeeklyHoroscopeDTO` | Top 15 unique aspects (group by transit+aspect+natal, best orb per key); slow bodies first |
| `MonthlyHoroscopeService` | `Profile`, date | `MonthlyHoroscopeDTO` | Top 15; fast transits: natal personal bodies only + active≥14 days; slow transits (body≥5): all natal bodies |
| `SolarReturnService` | `Profile`, year | `SolarReturnDTO` |
| `WeekdayHoroscopeService` | date | `WeekdayHoroscopeDTO`; reads `WeekdayText` model |

### Shared helpers
| Helper | Purpose |
|--------|---------|
| `Shared/AreasOfLifeScorer` | Scores 11 life areas from transit aspects; returns `AreaOfLifeDTO[]` |
| `Shared/LunationDetector` | Finds new/full moons in a date range from `planetary_positions` |
| `Shared/KeyDatesBuilder` | Finds key dates (exact aspects + lunations) for a period. Priority: 0=lunations, 1=slow transit body≥5, 2=fast. Orb cutoff: 1.5°. No hard limit — formula-driven. Moon (body=1) excluded. NNode/Lilith TBD (consultant Q6). |
| `Shared/ProgressedMoonCalculator` | Secondary progressions: 1 day = 1 year |

---

## Models

| Model | Table | Notes |
|-------|-------|-------|
| `User` | `users` | standard Laravel auth |
| `Guest` | `guest_sessions` | anonymous users |
| `Profile` | `profiles` | unified: user_id / guest_id / is_demo; implements `HoroscopeSubject` |
| `City` | `cities` | `astrotomic/laravel-translatable`; `$translatedAttributes = ['name']` |
| `CityTranslation` | `city_translations` | `locale`, `name`; no timestamps |
| `PlanetaryPosition` | `planetary_positions` | composite PK `[date, body]`; scopes: `forDate()`, `forBody()`, `retrograde()` |
| `NatalChart` | `natal_charts` | JSON: planets, aspects, houses, ascendant, mc |
| `NatalReport` | `natal_reports` | cached AI report; `mode` enum |
| `TextBlock` | `text_blocks` | `pick($key, $section, $variants)` static method |
| `WeekdayText` | `weekday_texts` | `iso_day` + `language` unique; no timestamps |

---

## DTOs (app/DataTransfer/)

### Horoscope/
- `DailyHoroscopeDTO` — positions, moon, retrogrades, transitNatalAspects, transitTransitAspects, areasOfLife, dayRuler
- `WeeklyHoroscopeDTO` — aspects[], retrogrades[], areasOfLife[], lunations[]
- `MonthlyHoroscopeDTO` — aspects[], retrogrades[], areasOfLife[], lunations[], keyDates[]
- `SolarReturnDTO` — solarReturnDatetime, cityName, solarAscSignIndex/Name, solarPlanets[], solarHouses[], solarNatalAspects[], progressedMoon[], progressedSun[], solarArcDirections[], lunations[], quarters[], retrogrades[]
- `WeekdayHoroscopeDTO` — iso_day, name, colors, gem, theme, description, rulerBody, rulerGlyph, number
- `AreaOfLifeDTO` — slug, name, rating (int 0–5), maxRating (int 5)
- `MoonDataDTO` — lunarDay, phaseSlug, phaseName, elongation, signIndex, signName
- `LunationDTO` — type (new_moon/full_moon), date, signIndex, signName, longitude
- `KeyDateDTO` — date, score, categories[], aspects[]
- `PlanetPositionDTO`, `RetrogradePlanetDTO`, `TransitAspectDTO`, `TransitTransitDTO`
- `SolarArcDirectionDTO` — directedBody/Name, natalTargetBody/Name, aspect, orb
- `QuarterDTO` — quarter (1–4), label, items[]
- `DayRulerDTO`, `ProgressedMoonDTO`

### Other
- `AiResponse` — text, inputTokens, outputTokens, costUsd
- `NatalReport`, `NatalReportSection`

---

## Architecture Rules — Horoscope Layer

These rules apply to every horoscope service, DTO, Artisan command, and future frontend function.

### 1. Services return pure data — no display text

**Services and DTOs must never contain:**
- Emojis (❤️ 🔮 ★ ⚠ etc.)
- Star-rating strings (`★★★☆☆`, `⚠ wait`)
- Hardcoded display names in any language
- Cyrillic or any non-ASCII human-readable text

**Services and DTOs must use:**
- Slugs for categorical values: `'love'`, `'new_moon'`, `'waxing_crescent'`, `'trine'`
- Numeric ratings: `rating: int` (0 = wait, 1–maxRating = stars) + `maxRating: int`
- Laravel `__('file.key')` for any name/label that appears in a DTO

### 2. Commands and frontend own all display mapping

Every Artisan command (and every future frontend component) owns:

```php
private const AREA_EMOJIS = [
    'love'            => '❤️',  'home'         => '🏠',
    'creativity'      => '🎨',  'spirituality' => '🔮',
    'health'          => '💚',  'finance'      => '💰',
    'travel'          => '✈️',  'career'       => '💼',
    'personal_growth' => '🌱',  'communication'=> '💬',
    'contracts'       => '📝',
];

private const MOON_PHASE_EMOJIS = [
    'new_moon' => '🌑', 'waxing_crescent' => '🌒',
    'first_quarter' => '🌓', 'waxing_gibbous' => '🌔',
    'full_moon' => '🌕', 'waning_gibbous' => '🌖',
    'last_quarter' => '🌗', 'waning_crescent' => '🌘',
];

// Canonical rating renderer — uses lang file
private function ratingDisplay(int $rating, int $maxRating): string
{
    if ($rating === 0) {
        return __('ui.rating_wait') . '  ';
    }
    return str_repeat('★', $rating) . str_repeat('☆', $maxRating - $rating) . '  ';
}
```

### 3. Internationalisation — lang files + `__()`

- All user-visible strings live in `lang/{locale}/` files
- Aspect names: `__('ui.aspects.trine')` with fallback `ucfirst(str_replace('_', ' ', $slug))`
- Area names: `__('areas.love')` etc.
- Moon phases: `__('lunar.phases.new_moon')` etc.
- Weekday data (name, colors, gem, theme): read from `weekday_texts` DB table — NOT lang files
- Error messages in Artisan commands may remain inline English

### 4. AI synthesis prompts must include a language instruction

```php
$langNote = $language !== 'en' ? "Write in language code: {$language}." : 'Write in English.';
$system   = "{$langNote}\n\n" . $system;
```

Never hardcode `'en'` — always pass `$language` through to every AI call.

### 5. UI display principle — glyphs + words, never symbols alone

```
☉ Sun in ♓ Pisces H8   ✓
☉ ♓ H8                 ✗
```

### 6. Orbs

- **Personalized horoscopes** (4.1–4.9, 4.13): orbs NOT shown in UI — internal use only
- **Public ephemeris** (4.10 Planet Positions): orbs ARE shown — educational/informational page

### 7. Areas of Life — scoring formula (approved by consultant)

Scoring is house/ruler-based — **personal**, not generic. Steps:

1. **Build day scores** (`AreasOfLifeScorer::buildDayScores`):
   - For each `transit_to_natal` aspect: `score[natal_body] += WEIGHT[aspect] × (1 - orb/maxOrb)`
   - Weights: trine=+2, sextile=+1, conjunction=+1, semi_sextile=0, quincunx=-1, square=-2, opposition=-2
   - Rx penalty: `-1` to the natal body of each retrograde planet

2. **Score categories** (`AreasOfLifeScorer::score`):
   - Each category maps to 1-2 natal houses (see matrix below)
   - For each house: find cusp sign → find traditional ruler → look up that ruler's score
   - If 2 houses: average the two ruler scores
   - `score100 = 50 + (score / 4) × 50` → clamped 0–100
   - Thresholds: ≥75 → ★★★★★, ≥55 → ★★★★, ≥42 → ★★★, ≥30 → ★★, <30 → ⚠

3. **House matrix** (0-indexed in code):

| Category | Houses (human) | Code indices |
|----------|---------------|-------------|
| ❤️ Love | 5th, 7th | [4, 6] |
| 🏠 Home | 4th | [3] |
| 🎨 Creativity | 5th | [4] |
| 🔮 Spirituality | 9th, 12th | [8, 11] |
| 💚 Health | 6th, 1st | [5, 0] |
| 💰 Finance | 2nd, 8th | [1, 7] |
| ✈️ Travel | 9th, 3rd | [8, 2] |
| 💼 Career | 10th | [9] |
| 🌱 Personal Growth | 1st | [0] |
| 💬 Communication | 3rd | [2] |
| 📝 Contracts | 7th, 3rd | [6, 2] |

**Sign rulers:** traditional (Mars for Scorpio, Saturn for Aquarius, Jupiter for Pisces).
**Requires natal houses** — only works for Tier 3 profiles (birth date + time + city).

### 8. Solar Return city

`Profile.solar_return_city_id` (nullable) overrides `birth_city_id` for solar return.
**No fallback** — throws `RuntimeException` if no city is available. Never default to Sofia or any hardcoded city.

---

## Contracts

- `HoroscopeSubject` — interface: `getBirthDate()`, `getBirthTime()`, `getBirthCity()`, `getChartTier()`, `isGuest()`, `isFull()`, `isPremium()`, `isDemo()`
- `AiProvider` — interface: `generate(string $prompt, string $system, int $maxTokens): AiResponse`

## Performance — Horoscope Cache (planned, Laravel phase)

**Pattern:** Lazy computed cache — calculate once, store in DB, serve from cache on repeat requests.

**Table:** `horoscope_cache`
```
profile_id  (FK → profiles)
type        (weekly | monthly | keydates_week | keydates_month | keydates_year | ...)
period      (YYYY-MM-DD — start of period)
data        (JSON — serialized DTO)
created_at
```

**Flow:**
1. Request arrives → check `horoscope_cache WHERE profile_id + type + period`
2. Found → deserialize JSON → return
3. Not found → calculate → INSERT into cache → return

**Invalidation — hash-based, no observers/listeners/events:**
Add `profile_hash` column to both cache tables.
```php
$hash = md5(implode('|', [
    $profile->birth_date,
    $profile->birth_time,
    $profile->birth_city_id,
    $profile->natalChart?->updated_at,
]));
```
On read: recompute hash → matches stored hash? → serve cache.
No match → recalculate → UPDATE row with new data + new hash.
Only astro-relevant fields trigger invalidation (not email, avatar, etc.).

**Why not Redis:** Results are profile-specific and long-lived (weeks/months) — DB is more appropriate than volatile cache.

**Areas of Life — dedicated table:** `profile_areas_of_life`
```
profile_id  (FK → profiles)
date        (YYYY-MM-DD — one row per day)
scores      (JSON — ['love'=>3, 'career'=>4, ...] — rating 0-5 per slug)
created_at
```
- Shared across all horoscope types (daily, weekly, monthly, key dates)
- Key dates inflection detection reads directly from this table (no recalculation)
- Invalidated on profile change: `DELETE WHERE profile_id = ?`

## Enums

- `ReportMode` — `Organic`, `Simplified`, `AiL1`, `AiL1Haiku`; `isAi(): bool`
