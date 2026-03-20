<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the ai_texts.type enum to include house_lords
        DB::statement("ALTER TABLE ai_texts MODIFY COLUMN type ENUM('introduction','section','transition','conclusion','house_lords') NOT NULL");

        // Add house_lords_ai_text_id to natal_reports
        Schema::table('natal_reports', function (Blueprint $table) {
            $table->foreignId('house_lords_ai_text_id')->nullable()->after('conclusion_ai_text_id')->constrained('ai_texts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('natal_reports', function (Blueprint $table) {
            $table->dropForeign(['house_lords_ai_text_id']);
            $table->dropColumn('house_lords_ai_text_id');
        });

        DB::statement("ALTER TABLE ai_texts MODIFY COLUMN type ENUM('introduction','section','transition','conclusion') NOT NULL");
    }
};
