# Console Commands & Cron Jobs

## Artisan Commands

_Document custom Artisan commands here as they are created._

| Command | Description | Usage |
|---------|-------------|-------|
| `horoscope:import-cities` | Import world cities (pop. 5000+) from GeoNames | `php artisan horoscope:import-cities` |

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

## Scheduled Tasks (Cron)

_Document all scheduled tasks defined in `routes/console.php`._

| Schedule | Command | Description |
|----------|---------|-------------|
| _(none yet)_ | | |

---

## Notes

- Ephemeris import (`horoscope:import-ephemeris`) — planned; runs once per year to populate `planetary_positions` table for 1920-2036
- Text import (`horoscope:import-texts`) — planned; imports curated text blocks into `text_blocks` table
