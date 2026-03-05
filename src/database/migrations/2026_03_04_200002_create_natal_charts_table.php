<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('natal_charts', function (Blueprint $table) {
            $table->id();

            // Polymorphic subject: User, GuestSession, DemoProfile
            $table->morphs('subject');

            $table->unsignedTinyInteger('chart_tier'); // 1, 2, or 3

            // Calculated planetary positions — array of {body, longitude, speed, is_retrograde}
            $table->json('planets');

            // Calculated aspects — array of {body_a, body_b, aspect, orb, applying}
            $table->json('aspects');

            // House cusps — null for Tier 1 (no birth time)
            $table->json('houses')->nullable();

            // Angles — null for Tier 1
            $table->decimal('ascendant', 9, 6)->nullable();
            $table->decimal('mc', 9, 6)->nullable();

            // No updated_at — chart is replaced on birth data change, never updated in place
            // morphs() already creates an index on (subject_type, subject_id)
            $table->timestamp('calculated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('natal_charts');
    }
};
