<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('text_blocks', function (Blueprint $table) {
            $table->id();

            // Block identifier — e.g. "sun_trine_moon", "moon_in_taurus_waxing"
            $table->string('key', 100);

            // Section — e.g. "daily", "weekly", "monthly", "natal", "transit"
            $table->string('section', 50);

            // Language — 'en', 'bg', 'de', etc.
            $table->string('language', 10)->default('en');

            // Variant number (1–8) — deterministic selection per user+date
            $table->unsignedTinyInteger('variant');

            // The actual text content
            $table->text('text');

            // Tone: 'positive', 'negative', 'neutral'
            $table->enum('tone', ['positive', 'negative', 'neutral'])->default('neutral');

            $table->timestamps();

            // One block = one key + section + language + variant combination
            $table->unique(['key', 'section', 'language', 'variant']);

            // Fast lookup by key + section + language
            $table->index(['key', 'section', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_blocks');
    }
};
