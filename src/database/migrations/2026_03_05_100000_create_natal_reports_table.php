<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI-generated text snippets (intro, transitions, conclusions, full sections)
        Schema::create('ai_texts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('natal_report_id');
            $table->enum('type', ['introduction', 'section', 'transition', 'conclusion']);
            $table->text('text');
            $table->timestamps();

            // FK added after natal_reports table exists
        });

        // Main report record — one per profile + mode + language
        Schema::create('natal_reports', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('profile_id');
            $table->foreign('profile_id')
                  ->references('id')
                  ->on('profiles')
                  ->cascadeOnDelete();

            // FK to natal_charts — cascade delete when chart is recalculated
            $table->unsignedBigInteger('natal_chart_id');
            $table->foreign('natal_chart_id')
                  ->references('id')
                  ->on('natal_charts')
                  ->cascadeOnDelete();

            $table->enum('mode', ['organic', 'simplified', 'ai_l1', 'ai_l2']);
            $table->string('language', 10)->default('en');

            // AI-generated introduction (L1/L2 only)
            $table->unsignedBigInteger('introduction_ai_text_id')->nullable();

            // AI-generated conclusion (L1/L2 only)
            $table->unsignedBigInteger('conclusion_ai_text_id')->nullable();

            $table->timestamps();

            $table->index(['profile_id', 'mode', 'language']);
            $table->index(['natal_chart_id', 'mode', 'language']);
        });

        // Report sections — relations, not duplicated text
        Schema::create('natal_report_sections', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('natal_report_id');
            $table->foreign('natal_report_id')
                  ->references('id')
                  ->on('natal_reports')
                  ->cascadeOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            // Block key, e.g. "sun_trine_moon", "ascendant_in_scorpio"
            $table->string('key', 100);

            // Section, e.g. "natal", "natal_ascendant", "natal_positions"
            $table->string('section', 50)->default('natal');

            // FK to text_blocks — organic/simplified/L1 modes
            $table->unsignedBigInteger('text_block_id')->nullable();
            $table->foreign('text_block_id')
                  ->references('id')
                  ->on('text_blocks')
                  ->nullOnDelete();

            // FK to ai_texts — L2 aspect text
            $table->unsignedBigInteger('ai_text_id')->nullable();

            // FK to ai_texts — L1/L2 transition paragraph
            $table->unsignedBigInteger('transition_ai_text_id')->nullable();

            $table->index(['natal_report_id', 'position']);
        });

        // Add FK from ai_texts back to natal_reports (deferred to avoid circular)
        Schema::table('ai_texts', function (Blueprint $table) {
            $table->foreign('natal_report_id')
                  ->references('id')
                  ->on('natal_reports')
                  ->cascadeOnDelete();
        });

        // Add FK from natal_reports to ai_texts for introduction/conclusion
        Schema::table('natal_reports', function (Blueprint $table) {
            $table->foreign('introduction_ai_text_id')
                  ->references('id')
                  ->on('ai_texts')
                  ->nullOnDelete();

            $table->foreign('conclusion_ai_text_id')
                  ->references('id')
                  ->on('ai_texts')
                  ->nullOnDelete();
        });

        // Add FK from natal_report_sections to ai_texts
        Schema::table('natal_report_sections', function (Blueprint $table) {
            $table->foreign('ai_text_id')
                  ->references('id')
                  ->on('ai_texts')
                  ->nullOnDelete();

            $table->foreign('transition_ai_text_id')
                  ->references('id')
                  ->on('ai_texts')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('natal_report_sections');
        Schema::dropIfExists('ai_texts');
        Schema::dropIfExists('natal_reports');
    }
};
