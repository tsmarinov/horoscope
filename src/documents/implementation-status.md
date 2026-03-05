# Implementation Status

Last updated: 2026-03-04

---

## ✅ Завършено

### Инфраструктура
- Docker setup (nginx:8085, php:8.2-fpm, mysql:33063, redis:6382)
- Laravel 12 проект
- Session driver: `database` (сесиите в MySQL, не Redis)

### Database миграции (всички изпълнени)
- `planetary_positions` — composite PK (date, body), 555,555 rows, 1920-2036, 13 тела
- `cities` + `city_translations` — 68,013 cities (GeoNames cities5000), само английски засега
- `guest_sessions` — UUID cookie, last_seen_at, IP, user_agent
- `user_profiles`, `guest_profiles` (с name), `demo_profiles` — отделни от auth таблицата
- `natal_charts` — polymorphic (User / GuestSession / DemoProfile), JSON planets/aspects/houses

### Модели
- `PlanetaryPosition` — body constants, SIGN_NAMES, BODY_NAMES, scopes: forDate/forBody/retrograde
- `City` + `CityTranslation` — astrotomic/laravel-translatable, scopeSearch, scopeByCountry
- `User` — auth-only, implements HoroscopeSubject чрез delegation към profile
- `UserProfile` — birth_date, birth_time, birth_time_approximate, birth_city_id, getChartTier()
- `GuestSession` — UUID factory, findOrCreateFromCookie() (placeholder), morphOne(NatalChart)
- `GuestProfile` — implements HoroscopeSubject, isGuest()=true
- `DemoProfile` — implements HoroscopeSubject, isDemo()=true, slug route key
- `NatalChart` — polymorphic subject, JSON casts, timestamps=false

### Contracts & Support
- `HoroscopeSubject` interface — getBirthDate/Time/City, getChartTier, isGuest/isFull/isPremium/isDemo
- `GuestSubject` — чист PHP обект за unit тестове (fromSession() премахнат)

### AspectCalculator ✅ одобрено от консултант
- Фасада `App\Facades\AspectCalculator`
- Calculate-or-load: зарежда от DB ако вече е изчислено, иначе изчислява и записва
- GuestSubject → unsaved NatalChart (не се записва)
- Tier 2/3 интерполация: `longitude + speed × UTC_fraction` (DST-коректно с Carbon + IANA timezone)
- **Орбове (натална карта + транзити):**
  - Sun (всеки аспект): 5°
  - Moon (всеки аспект): 4°
  - Conjunction: 3°, Opposition: 3°, Square: 3°
  - Trine: 2°, Sextile: 2°, Quincunx (150°): 2°
  - Semi-sextile (30°): 1°
  - Semi-square и sesquiquadrate — **премахнати**
- **Lilith (body=12):** само conjunction с планети, никакви други аспекти
- **Взаимна рецепция (mutual_reception):** детектира се като слаб conjunction
  - Модерни рулерства (Уран→Водолей, Нептун→Риби, Плутон→Скорпион)
  - Важи за натална карта и транзити
  - **Не важи** за дирекции и прогресии
- `progression_orb: 1.0°` — конфигуриран в config/astrology.php за бъдещо ползване

### Seeders & Commands
- `TestUserSeeder` — test@horo.test / password, рожд. 1985-03-15, 02:15, London (city_id=24924)
- `ImportCities` command — download + parse GeoNames, insertOrIgnore, --fresh / --skip-download
- `HoroscopeTest` command — зарежда test user, dump NatalChart

---

## 🔲 Предстоящо (по етапи от плана)

### Етап 1 доизграждане
- [ ] `user_audit_log` миграция + модел (GDPR — survives account deletion)
- [ ] `sessions` таблица миграция (Laravel session driver=database)
- [ ] `demo_profiles` seeder с известни хора

### Етап 2 — Guest Middleware
- [ ] Middleware за UUID cookie → `GuestSession::findOrCreateFromCookie()`
- [ ] Cron за чистене на неактивни guest_sessions (> 3 месеца)

### Етап 3 — Изчислителен двигател
- [ ] `AspectScorer` — orb + планети → score 0-100, cusp_proximity_factor, stellium_bonus
- [ ] `CriticalDatesFinder` — период → топ дати по score
- [ ] Houses / ASC / MC изчисление (Tier 2/3) — засега null в NatalChart
- [ ] Транзити — аспекти между транзитни и натални позиции
- [ ] Дирекции и прогресии (progression_orb=1°, без mutual_reception)
- [ ] `ReportBuilder` — избира текстови блокове по активни аспекти
- [ ] `VariantPicker` — user_id + date → детерминистичен seed → вариант

### Етап 3 — Auth
- [ ] Регистрация (email + password)
- [ ] Email потвърждение (host SMTP от DO droplet)
- [ ] Login / logout
- [ ] Profile (edit birth data, delete account с cascade)
- [ ] Claim flow: GuestSession → User при регистрация (remapване на natal_charts)
- [ ] Social login — Socialite (нисък приоритет)

### Етап 4 — Текстова база
- [ ] `text_blocks` миграция — key, language, variant, text
- [ ] `horoscope:generate-texts` command (Anthropic API batch)
- [ ] `horoscope:import-texts` command
- [ ] `horoscope:review-texts` command

### Етап 5 — API
- [ ] POST /api/register, /api/login
- [ ] GET /api/horoscope/daily/{date}, /weekly, /monthly, /yearly
- [ ] GET /api/lunar-calendar/{year}/{month}
- [ ] GET /api/retrograde-calendar/{year}
- [ ] GET /api/natal-chart
- [ ] GET /api/user/profile
- [ ] Кеширане в `cached_reports`
- [ ] Accept-Language / ?lang= поддръжка

### Етап 6 — Уеб
- [ ] Landing page (Blade + Tailwind)
- [ ] Demo pages (съкратена прогноза)
- [ ] SEO / Blog pages

### Етап 7 — Android
- [ ] Kotlin + Retrofit API клиент
- [ ] AdMob реклами

### Бъдещо (Етап 8)
- [ ] iOS приложение
- [ ] Freemium монетизация
- [ ] Premium AI-генерирани прогнози на живо
- [ ] ×5→×8 варианти на текстов блок

---

## Терминология — изяснено

- **Планетни позиции** (`/planet-positions`) — обща страница без login: транзитни планети + аспекти между транзитите. Без натална карта, без персонален контекст.
- **Транзити** (`/transits`) — лична страница с натална карта: аспекти между транзитни и натални планети. Изисква рождени данни.

Демото `planetpositions-demo.html` съдържа грешна `нат.` колона — не трябва да е там.

---

## Астрологически формули — изяснено от консултант

### Домове
- Система: **Placidus**

### Прогресии и дирекции
- **Secondary progressions** — 1 ден след раждане = 1 година живот; орбис 1° за всички
- **Solar Arc Directions** — всички планети се движат с ~1° годишно равномерно (колкото Слънцето); орбис 1° за всички
- И двата метода: без взаимна рецепция

### Синастрия
- Орбове: **същите като наталната карта** (Sun 5°, Moon 4°, останалите по аспект)
- Метод: кръстосани аспекти A×B (не A×A или B×B)

### Хороскоп на връзката (composite)
- Метод: **midpoint** (средна точка на всяка планетна двойка)

### Ключови дати — критерии
- Аспекти от **Сатурн** и **Юпитер** (транзитни към натални)
- **Лунации** — новолуние и пълнолуние
- **Съвпади** на всяка транзитна планета с **10-ти дом (MC)** и **Асцендент**
- **Стелиуми** по дом и по знак
- Показват се **и позитивни, и негативни** ключови дати — филтър по минимален score (праг за изключване на слаби)

### AspectScorer ✅ одобрено от консултант

**Формула:** `score = aspect_base × orb_factor × cusp_factor`

**aspect_base (0-100):**
| Аспект | Base |
|--------|------|
| Conjunction | 100 |
| Opposition | 85 |
| Trine | 70 |
| Square | 65 |
| Sextile | 55 |
| Quincunx | 40 |
| Mutual reception | 20 (слаб) |

**orb_factor:** `1 - (orb / max_orb)` → 0° орбис = пълен score, максимален орбис → 0

**cusp_factor** (ако планета е в 3° от куспид на дом):
| Тип дом | Домове | Bonus |
|---------|--------|-------|
| Angular | 1, 10, 7, 4 | +20% |
| Succedent | 2, 5, 8, 11 | +10% |
| Cadent | 3, 6, 9, 12 | +5% |

**Планетни тежести:** не влизат — орбовете (Sun 5°, Moon 4°) отразяват силата
**Ретрограден:** няма ефект върху score-а
**stellium_bonus:** отделен score за стелиума като цяло (3+ планети в един дом/знак); стойността се определя спрямо балната система

**Балова система (конфигуруема):**
- 3-звездна или 5-звездна
- Минимален score за ключова дата = конфигуруем % (при 5-звездна → 20% от максимума)

**Позитивен/негативен score:**
- Тригон, секстил, mutual_reception → позитивен
- Квадрат, опозиция → негативен
- Конюнкция → зависи от планетите

**Void-of-course Луна** — Луната не прави аспекти преди смяна на знака; включват се: conjunction, opposition, trine, square, sextile, quincunx, semi_sextile

**Mutual reception score:** 20 (потвърдено)

**Composite:** всичките 13 тела (включително Chiron, NNode, Lilith), midpoint метод

**Синастрия — взаимна рецепция:** включена, но като по-слабо енергийно влияние (score по-нисък от стандартната mutual_reception в натала)

### Лунен календар
- Лунни фази (новолуние, пълнолуние, първа четвърт, последна четвърт)
- Void-of-course Луна
- Смяна на знак от Луната

---

## Конфигурация — важни бележки

- Орбовете за **дирекции/прогресии** са **1° за всички** — имплементира се при изграждане на тези функции
- **Взаимна рецепция** — само за натална карта и транзити, не за дирекции/прогресии
- Houses/ASC/MC — изчисляват се само при Tier 2 и 3; ще изисква Swiss Ephemeris PHP binding или предварително изчисление
