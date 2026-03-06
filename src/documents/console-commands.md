# Console Commands & Cron Jobs

## Artisan Commands

_Document custom Artisan commands here as they are created._

| Command | Description | Usage |
|---------|-------------|-------|
| `horoscope:import-cities` | Import world cities (pop. 5000+) from GeoNames | `php artisan horoscope:import-cities` |
| `horoscope:generate-natal` | Generate natal aspect text blocks (473 keys) | `php artisan horoscope:generate-natal` |
| `horoscope:generate-natal-synthesis` | Generate natal position texts (732 keys: ASC + planets in sign+house) | `php artisan horoscope:generate-natal-synthesis` |
| `horoscope:generate-lunar` | Generate lunar calendar text blocks (moon-in-sign, lunation house, lunation sign) | `php artisan horoscope:generate-lunar` |
| `horoscope:ui-daily-report` | CLI pseudo-browser preview: daily horoscope report | `php artisan horoscope:ui-daily-report {profile}` |
| `horoscope:ui-lunar-calendar` | CLI pseudo-browser preview: lunar calendar with personalized lunation cards | `php artisan horoscope:ui-lunar-calendar {profile?} --month=YYYY-MM` |
| `horoscope:ui-weekday` | CLI pseudo-browser preview: Days of the Week reference page (all 7 days, today highlighted, clothing tip if profile) | `php artisan horoscope:ui-weekday {profile?} --date=YYYY-MM-DD` |

### horoscope:import-cities

Populates `cities` + `city_translations` (locale `en`) from GeoNames `cities5000.txt`.

**Source:** https://download.geonames.org/export/dump/cities5000.zip (~47K cities, population ≥ 5000)

**Options:**
- `--fresh` — truncate both tables before import (full re-import)
- `--skip-download` — use already-downloaded file at `storage/app/geonames/cities5000.txt`

**Normal usage (first time):**
```bash
docker exec horo_php php artisan horoscope:import-cities
```

**Re-import from scratch:**
```bash
docker exec horo_php php artisan horoscope:import-cities --fresh
```

**Re-run without re-downloading:**
```bash
docker exec horo_php php artisan horoscope:import-cities --skip-download
```

The command is idempotent — uses `insertOrIgnore` so duplicate runs are safe without `--fresh`.

---

### horoscope:generate-natal

Generates natal aspect text blocks (body_a aspect body_b) using the Anthropic API.

**Section:** `natal`
**Keys:** 473 (all aspect combinations for Sun, Moon, Mercury, Venus, Mars, Jupiter, Saturn, Uranus, Neptune, Pluto, Chiron, Lilith, Node)
**Options:**
- `--variants=3` — variants per block (default: 3)
- `--from-key=` — resume from a specific key
- `--key=` — generate only one specific key
- `--dry-run` — show prompt and response without saving
- `--model=` — Anthropic model (default: `claude-haiku-4-5-20251001`)

```bash
docker exec horo_php php artisan horoscope:generate-natal --variants=3
docker exec horo_php php artisan horoscope:generate-natal --from-key=sun_conjunction_moon
```

Cost reference: 473 keys × 3 variants ≈ $1.00

---

### horoscope:generate-natal-synthesis

Generates natal position text blocks: Ascendant in sign + planet in sign + house.

**Sections:** `natal_ascendant` (12 keys), `natal_positions` (720 keys), `natal_ascendant_short`, `natal_positions_short`
**Options:**
- `--variants=3` — variants per block
- `--short` — generate 1-sentence simplified variants (`_short` sections)
- `--from-key=`, `--key=`, `--dry-run`, `--model=`

```bash
docker exec horo_php php artisan horoscope:generate-natal-synthesis --variants=3
docker exec horo_php php artisan horoscope:generate-natal-synthesis --short
```

Cost reference: 732 keys × 1 variant ≈ $1.06

---

### horoscope:generate-lunar

Generates lunar calendar text blocks.

**Types:**
- `lunar_day` — Moon in sign (12 keys, ~2-day transit descriptions, impersonal)
- `lunation_house` — New/Full Moon in house (24 keys, personalized "you" address)
- `lunation_sign` — New/Full Moon in sign taglines (24 keys, plain text, max 10 words)

**Options:**
- `--type=lunar_day` — section type (default: `lunar_day`)
- `--variants=1` — variants per block
- `--from-key=`, `--key=`, `--dry-run`, `--model=`

```bash
docker exec horo_php php artisan horoscope:generate-lunar --type=lunar_day
docker exec horo_php php artisan horoscope:generate-lunar --type=lunation_house
docker exec horo_php php artisan horoscope:generate-lunar --type=lunation_sign
```

**Note:** All 3 types (12 + 24 + 24 = 60 texts) were hand-written directly — too small a set for AI generation to be cost-effective or to produce better quality.

---

### horoscope:ui-daily-report

CLI pseudo-browser preview of the daily horoscope report for a profile.

```bash
docker exec horo_php php artisan horoscope:ui-daily-report 1
docker exec horo_php php artisan horoscope:ui-daily-report 1 --date=2026-06-21
```

---

### horoscope:ui-lunar-calendar

CLI pseudo-browser preview of the lunar calendar for a given month. Shows calendar grid, personalized lunation cards (with house placement and natal conjunctions), day-by-day moon-in-sign descriptions, and lunation taglines.

```bash
docker exec horo_php php artisan horoscope:ui-lunar-calendar          # anonymous, current month
docker exec horo_php php artisan horoscope:ui-lunar-calendar 1        # profile ID 1
docker exec horo_php php artisan horoscope:ui-lunar-calendar 1 --month=2026-06
```

---

### horoscope:ui-weekday

CLI pseudo-browser preview of the Days of the Week reference page. Shows all 7 weekdays with ruler, colors, gemstone, numerology number, theme keywords, and a 2-sentence description. Today's day is highlighted. If a profile is supplied, shows a personalized clothing & jewelry tip for today based on natal Venus sign.

Clothing tip texts are pre-generated (`weekday_clothing` section, 84 texts: 7 days × 12 Venus signs).

```bash
docker exec horo_php php artisan horoscope:ui-weekday          # no profile, today
docker exec horo_php php artisan horoscope:ui-weekday 1        # profile 1, today
docker exec horo_php php artisan horoscope:ui-weekday 1 --date=2026-06-13
```

---

## Scheduled Tasks (Cron)

_Document all scheduled tasks defined in `routes/console.php`._

| Schedule | Command | Description |
|----------|---------|-------------|
| _(none yet)_ | | |

---

## Notes

- Ephemeris import (`horoscope:import-ephemeris`) — planned; runs once per year to populate `planetary_positions` table for 1920-2036
- Text import (`horoscope:import-texts`) — planned; imports curated text blocks into `text_blocks` table
