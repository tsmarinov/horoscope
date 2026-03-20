# AGENTS.md — database/

## Current Migrations
| File | Table | Description |
|------|-------|-------------|
| `000000_create_users_table` | `users` | Auth users |
| `000001_create_cache_table` | `cache` | Laravel cache store |
| `000002_create_jobs_table` | `jobs` | Queue jobs |
| `2026_03_04_123332_create_planetary_positions_table` | `planetary_positions` | Ephemeris data 1920-2036 |
| `2026_03_04_150449_create_cities_table` | `cities` + `city_translations` | World cities 5000+ pop, translatable names |

| `2026_03_04_200000_extend_users_table` | `users` | Adds birth_date, birth_time, birth_time_approximate, birth_city_id |
| `2026_03_04_200001_create_demo_profiles_table` | `demo_profiles` | Public SEO demo profiles |

## Planned Migrations (in order)
1. `user_natal_charts` — user_id (FK cascadeOnDelete), planets (JSON), aspects (JSON), houses (JSON nullable), ascendant/mc (nullable), chart_tier, calculated_at
3. `planetary_positions` — `date`, `planet_id`, `longitude`, `latitude`, `speed`; ~584K rows (1920-2036); consider yearly partitioning
4. `text_blocks` — `section`, `block_key`, `variant_index` (0-7), `teaser` (varchar 255), `content` (text); index on `(section, block_key, variant_index)`
5. `user_natal_charts` — `user_id` (FK), `planets` (JSON), `houses` (JSON), `cached_at`
6. `synastry_partners` — `user_id` (FK), partner birth data; max 10 per user
7. `cached_reports` — `user_id`, `report_type`, `report_date`, `content` (longtext), `expires_at`

## Rules for Agents
- **Migrations are irreversible in production** — write `down()` only if it is safe and meaningful
- **Indexes are required** for: `planetary_positions(date, planet_id)`, `text_blocks(section, block_key, variant_index)`, `cached_reports(user_id, report_type, report_date)`
- **JSON columns** in MySQL 8.3 — use `->json()` for planets/houses in natal charts
- **Never delete data** from `text_blocks` or `planetary_positions` in migrations — use seeders or separate commands
- Naming convention: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`
