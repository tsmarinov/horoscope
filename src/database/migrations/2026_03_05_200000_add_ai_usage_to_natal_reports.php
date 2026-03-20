<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('natal_reports', function (Blueprint $table) {
            $table->unsignedInteger('ai_tokens_in')->default(0)->after('language');
            $table->unsignedInteger('ai_tokens_out')->default(0)->after('ai_tokens_in');
            $table->decimal('ai_cost_usd', 10, 6)->default(0)->after('ai_tokens_out');
        });
    }

    public function down(): void
    {
        Schema::table('natal_reports', function (Blueprint $table) {
            $table->dropColumn(['ai_tokens_in', 'ai_tokens_out', 'ai_cost_usd']);
        });
    }
};
