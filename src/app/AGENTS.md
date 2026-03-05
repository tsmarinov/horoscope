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
