# AGENTS.md — routes/

## Current State
- `web.php` — only `GET /` → welcome view
- `api.php` — does not exist yet

## Planned Web Routes
```php
// Public
GET /                    // Landing page
GET /horoscope/daily     // Daily forecast
GET /horoscope/weekly    // Weekly
GET /horoscope/monthly   // Monthly
GET /horoscope/yearly    // Yearly
GET /lunar-calendar      // Lunar calendar
GET /retrograde          // Retrograde calendar
GET /natal-chart         // Natal chart
GET /synastry            // Synastry

// Auth required (middleware: auth)
GET  /profile
POST /profile
```

## Planned API Routes (api.php — to be created)
```php
// Public
POST /api/register
POST /api/login

// Auth required (middleware: auth:sanctum)
GET /api/horoscope/daily/{date}
GET /api/horoscope/weekly/{date}
GET /api/horoscope/monthly/{date}
GET /api/horoscope/yearly/{year}
GET /api/lunar-calendar/{year}/{month}
GET /api/retrograde-calendar/{year}
GET /api/natal-chart
GET /api/synastry/{partner_id}
GET /api/relationship-forecast/{partner_id}/{period}
GET /api/user/profile
PUT /api/user/profile
```

## Rules for Agents
- API routes use `auth:sanctum` — install Sanctum before creating `api.php`
- Web horoscope pages can be public with limited (Tier 1) content for non-authenticated users
- Group routes with `Route::middleware()->group()`
- Name routes: `horoscope.daily`, `horoscope.weekly`, etc.
