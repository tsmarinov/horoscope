<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('text_blocks', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->default(null)->after('language');
        });

        // Drop old unique index and create new one including gender
        Schema::table('text_blocks', function (Blueprint $table) {
            $table->dropUnique('text_blocks_key_section_language_variant_unique');
            $table->unique(['key', 'section', 'language', 'variant', 'gender'], 'text_blocks_key_section_language_variant_gender_unique');
        });

        // Update lookup index to include gender
        Schema::table('text_blocks', function (Blueprint $table) {
            $table->dropIndex('text_blocks_key_section_language_index');
            $table->index(['key', 'section', 'language', 'gender'], 'text_blocks_key_section_language_gender_index');
        });
    }

    public function down(): void
    {
        Schema::table('text_blocks', function (Blueprint $table) {
            $table->dropIndex('text_blocks_key_section_language_gender_index');
            $table->index(['key', 'section', 'language'], 'text_blocks_key_section_language_index');
        });

        Schema::table('text_blocks', function (Blueprint $table) {
            $table->dropUnique('text_blocks_key_section_language_variant_gender_unique');
            $table->unique(['key', 'section', 'language', 'variant'], 'text_blocks_key_section_language_variant_unique');
        });

        Schema::table('text_blocks', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
