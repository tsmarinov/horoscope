<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Planetary body constants (match Swiss Ephemeris SE_* values used in generator):
     *  0 = Sun       1 = Moon      2 = Mercury   3 = Venus     4 = Mars
     *  5 = Jupiter   6 = Saturn    7 = Uranus    8 = Neptune   9 = Pluto
     * 10 = Chiron   11 = NNode (Mean)  12 = Lilith (Mean Apogee)
     *
     * South Node = NNode + 180° — calculated on the fly, not stored.
     * Total: ~42 000 days × 13 bodies ≈ 546 000 rows.
     */
    public function up(): void
    {
        Schema::create('planetary_positions', function (Blueprint $table) {
            $table->date('date');
            $table->unsignedTinyInteger('body'); // 0–12, see constants above

            // Ecliptic longitude 0.000000–359.999999°
            $table->decimal('longitude', 9, 6);

            // Daily motion in degrees; negative = retrograde
            $table->decimal('speed', 8, 6);

            // Denormalised flag for fast retrograde-calendar queries
            $table->boolean('is_retrograde')->default(false);

            $table->primary(['date', 'body']);

            // All bodies for a given date (most common query pattern)
            $table->index('date');

            // Retrograde calendar: which dates is body X retrograde?
            $table->index(['body', 'is_retrograde', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planetary_positions');
    }
};
