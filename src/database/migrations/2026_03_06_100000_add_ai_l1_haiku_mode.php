<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE natal_reports MODIFY COLUMN mode ENUM('organic','simplified','ai_l1','ai_l1_haiku') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE natal_reports MODIFY COLUMN mode ENUM('organic','simplified','ai_l1') NOT NULL");
    }
};
