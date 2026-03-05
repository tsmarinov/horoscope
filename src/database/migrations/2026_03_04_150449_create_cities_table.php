<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cities with translatable names (via astrotomic/laravel-translatable).
     *
     * Source: GeoNames cities5000.txt (~47 000 cities with population > 5 000)
     *   https://download.geonames.org/export/dump/cities5000.zip
     *
     * city_translations holds one row per city per locale ('en', 'bg', …).
     * English is the default/fallback locale.
     *
     * Autocomplete query:
     *   SELECT c.*, t.name
     *   FROM cities c
     *   JOIN city_translations t ON t.city_id = c.id AND t.locale = 'en'
     *   WHERE t.name LIKE 'Sof%'
     *   ORDER BY c.population DESC
     *   LIMIT 10;
     */
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();

            // GeoNames unique identifier — useful for future data refreshes
            $table->unsignedInteger('geonames_id')->unique();

            $table->char('country_code', 2)->index();  // ISO 3166-1 alpha-2

            $table->decimal('lat', 8, 5);
            $table->decimal('lng', 8, 5);

            // IANA timezone identifier (e.g. 'Europe/Sofia', 'America/New_York')
            $table->string('timezone', 50);

            // Used to rank autocomplete results (more populated cities first)
            $table->unsignedInteger('population')->default(0);

            $table->timestamps();
        });

        Schema::create('city_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 200);

            $table->unique(['city_id', 'locale']);

            // Fast prefix search for autocomplete: WHERE name LIKE 'Sof%'
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_translations');
        Schema::dropIfExists('cities');
    }
};
