# AGENTS.md — Horo Laravel Application

## Project
Astrological horoscope platform — Laravel 12 monolith serving both a web site (Blade) and a JSON API for Android/iOS clients.

## External References
> ⚠ These paths are local-only and will not be present in the git repository.

- **Project plan:** `/var/www/horo/project/astro-horoscope-project-plan.md` — full architecture, section breakdown, text block counts, DB schema design
- **Demo texts:** `/var/www/horo/project/*.html` — HTML demos for each horoscope section (daily, weekly, monthly, natal, synastry, transits, etc.)

## Stack
- PHP 8.2 / Laravel 12
- MySQL 8.3 (host: `horo_mysql`, port: 33063, db: `horo`)
- Redis (host: `horo_redis`, port: 6382) — cache + queue
- Nginx (port: 8085) + PHP-FPM (Docker)
- Vite 7 + Tailwind CSS 4 (frontend build)
- PHPUnit 11

## Directory Map

```
src/
├── app/
│   ├── Http/Controllers/   # Controllers (only base class for now)
│   ├── Models/             # Eloquent models
│   ├── Services/           # (planned) AspectCalculator, ReportBuilder, VariantPicker...
│   └── Providers/          # AppServiceProvider
├── config/                 # app, database, cache, queue, session config
├── database/
│   ├── migrations/         # Database schema
│   ├── factories/          # Model factories (empty)
│   └── seeders/            # Seeders (empty)
├── resources/
│   ├── views/              # Blade templates
│   ├── js/                 # app.js (Vite entry), bootstrap.js (Axios)
│   └── css/                # Tailwind CSS
├── routes/
│   ├── web.php             # Web routes (only GET / for now)
│   └── console.php         # Artisan commands
├── tests/
│   ├── Feature/
│   └── Unit/
└── public/                 # Web root (index.php)
```

## Current State (Phase 1 — Scaffold)
- Only `GET /` route → `welcome` view
- `routes/api.php` does not exist yet
- `app/Services/` does not exist yet
- Migrations: only `users`, `cache`, `jobs`
- `User` model: only `name`, `email`, `password`

## Planned Architecture

### Services (app/Services/)
| Service | Purpose |
|---------|---------|
| `AspectCalculator` | Calculates planetary aspects (pure PHP, reads from `planetary_positions` DB table) |
| `AspectScorer` | Scores aspects 0-100 with multipliers (cusp_proximity ×0.8-1.8, stellium_bonus ×1.2-1.4) |
| `ReportBuilder` | Assembles text blocks into a narrative horoscope |
| `VariantPicker` | Deterministic variant selection (seed = user_id + date) |
| `TextRepository` | Queries `text_blocks` table + Redis cache |
| `SynastryCalculator` | Aspects between two natal charts |
| `SynastryScorer` | Scores synastry by category (romantic, business, friendship, family) |
| `NatalSynthesisGenerator` | Assembles natal chart interpretation |
| `SolarReturnCalculator` | Finds exact Sun return moment (Newton-Raphson, precise to second) |
| `CriticalDatesFinder` | Finds key dates in a period using AspectScorer results |

### Planned Models
| Model | Table | Purpose |
|-------|-------|---------|
| `User` | `users` | Extended with birth_date, birth_time, birth_city_id, chart_tier |
| `City` | `cities` | ~40K rows with geo data |
| `UserNatalChart` | `user_natal_charts` | Cached natal chart (JSON planets + houses) |
| `PlanetaryPosition` | `planetary_positions` | ~584K rows, 1920-2036 ephemeris data |
| `TextBlock` | `text_blocks` | ~57K text variants (7,200 blocks × 8 variants) |
| `CachedReport` | `cached_reports` | Rendered horoscope by user + type + date |
| `SynastryPartner` | `synastry_partners` | Saved partners (max 10 per user) |

### Planned Routes
**Web:** `/horoscope/daily`, `/weekly`, `/monthly`, `/yearly`, `/natal-chart`, `/synastry`, `/lunar-calendar`, `/retrograde`, `/profile`

**API:** `POST /api/register|login`, `GET /api/horoscope/{type}/{date}`, `/api/natal-chart`, `/api/synastry/{partner_id}`, `/api/user/profile`

## Key Design Principles
1. **Organic text** — narrative prose, not catalogues. Text blocks are paragraphs, not bullet lists of astrological facts.
2. **Determinism** — same variant shown on re-reads. Seed: `hash(user_id + date)`.
3. **Ephemeris as data** — Python/Swiss Ephemeris runs once per year (`horoscope:import-ephemeris` command), results stored in DB. Runtime reads are SQL only, no binary dependencies.
4. **Chart Tiers** — Tier 1 (date only) / Tier 2 (approximate time) / Tier 3 (exact time + city). UI locks content above the user's tier.
5. **AI-ready** — `ReportBuilder` supports both modes (`assembleFromBlocks` / `generateWithAI`) transparently to API consumers.

## Dev Commands
```bash
composer setup    # Install deps + migrate + build frontend
composer run dev  # serve + queue + pail + vite (concurrent)
composer run test # config:clear + phpunit
```

## Agent Guidelines
- **Do not modify `vendor/`**
- **Do not create `routes/api.php`** without also setting up the auth middleware (Sanctum)
- **Services live in `app/Services/`** — one class per file
- **Migrations are irreversible in production** — be careful with `down()` methods
- **Text blocks always have 8 variants** (`variant_index` 0-7)
- **Redis cache key convention:** `horo:{type}:{user_id}:{date}`
