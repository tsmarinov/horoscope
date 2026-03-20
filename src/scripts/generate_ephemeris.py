#!/usr/bin/env python3
"""
Ephemeris generator — Swiss Ephemeris → planetary_positions.csv

Generates daily planetary positions (noon UTC) for 1920-01-01 to 2036-12-31
and writes a CSV ready for MySQL LOAD DATA INFILE.

Usage (Docker):
    docker run --rm \
      -v /var/www/horo/web/src/scripts:/data \
      -v /var/www/horo/web/src/scripts/ephe:/ephe \
      python:3.12 \
      sh -c "pip install pyswisseph -q && python3 /data/generate_ephemeris.py [--test]"

    --test  : generate only Jan 2026 (quick sanity check, ~390 rows)

Ephemeris files (place in /var/www/horo/web/src/scripts/ephe/ before running):
    seas_18.se1  — asteroids 1800-2400 AD (required for Chiron)
    Download from: https://www.astro.com/ftp/swisseph/ephe/seas_18.se1

Body mapping stored in planetary_positions.body column:
    0  Sun        1  Moon       2  Mercury    3  Venus      4  Mars
    5  Jupiter    6  Saturn     7  Uranus     8  Neptune    9  Pluto
    10 Chiron     11 NNode(Mean) 12 Lilith(MeanApog)

    South Node = NNode + 180° — calculated at query time, not stored.
"""

import csv
import sys
from datetime import date, timedelta
from pathlib import Path

import swisseph as swe

# ---------------------------------------------------------------------------
# Body map: our DB id → Swiss Ephemeris constant
# ---------------------------------------------------------------------------
BODIES = {
    0:  swe.SUN,
    1:  swe.MOON,
    2:  swe.MERCURY,
    3:  swe.VENUS,
    4:  swe.MARS,
    5:  swe.JUPITER,
    6:  swe.SATURN,
    7:  swe.URANUS,
    8:  swe.NEPTUNE,
    9:  swe.PLUTO,
    10: swe.CHIRON,       # requires seas_18.se1
    11: swe.MEAN_NODE,    # Mean North Node — always slightly retrograde
    12: swe.MEAN_APOG,    # Mean Black Moon Lilith — always direct
}

FULL_START = date(1920, 1, 1)
FULL_END   = date(2036, 12, 31)
TEST_START = date(2026, 1, 1)
TEST_END   = date(2026, 1, 31)

EPHE_PATH  = "/ephe"
OUTPUT     = "/data/planetary_positions.csv"

# ecliptic longitude + speed, using Swiss Ephemeris files where available,
# falling back to Moshier built-in for planets
FLAGS = swe.FLG_SWIEPH | swe.FLG_SPEED


def julian_day(d: date) -> float:
    """Julian Day Number for noon UTC on the given date."""
    return swe.julday(d.year, d.month, d.day, 12.0)


def generate(start: date, end: date) -> None:
    swe.set_ephe_path(EPHE_PATH)

    total_days = (end - start).days + 1
    print(f"Generating {total_days} days × {len(BODIES)} bodies "
          f"({start} → {end}) …", flush=True)

    skipped: dict[int, int] = {}  # body_id → count of skipped days

    with open(OUTPUT, "w", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["date", "body", "longitude", "speed", "is_retrograde"])

        written = 0
        current = start

        while current <= end:
            jd       = julian_day(current)
            date_str = current.isoformat()

            for body_id, se_const in BODIES.items():
                try:
                    xx, _ = swe.calc_ut(jd, se_const, FLAGS)
                except swe.Error as e:
                    skipped[body_id] = skipped.get(body_id, 0) + 1
                    # do NOT advance `current` here — just skip this body
                    continue

                longitude = round(xx[0] % 360, 6)   # normalise 0–360
                speed     = round(xx[3], 6)          # degrees/day; negative = Rx
                is_rx     = 1 if speed < 0 else 0

                writer.writerow([date_str, body_id, longitude, speed, is_rx])
                written += 1

            # progress every ~1 year
            days_done = (current - start).days + 1
            if days_done % 365 == 0 or current == end:
                pct = days_done / total_days * 100
                print(f"  {current}  {pct:5.1f}%  ({written:,} rows)", flush=True)

            current += timedelta(days=1)

    print(f"\nDone. {written:,} rows → {OUTPUT}")

    if skipped:
        print("\nSkipped rows by body (missing ephemeris file):")
        names = {10: "Chiron (needs seas_18.se1)", 11: "NNode", 12: "Lilith"}
        for body_id, count in sorted(skipped.items()):
            label = names.get(body_id, f"body {body_id}")
            print(f"  {label}: {count} days skipped")


if __name__ == "__main__":
    test_mode = "--test" in sys.argv
    start = TEST_START if test_mode else FULL_START
    end   = TEST_END   if test_mode else FULL_END

    if test_mode:
        print("TEST MODE — generating Jan 2026 only")

    if not Path(f"{EPHE_PATH}/seas_18.se1").exists():
        print(f"WARNING: {EPHE_PATH}/seas_18.se1 not found — Chiron will be skipped.")
        print("  Download from: https://www.astro.com/ftp/swisseph/ephe/seas_18.se1")
        print()

    generate(start, end)
