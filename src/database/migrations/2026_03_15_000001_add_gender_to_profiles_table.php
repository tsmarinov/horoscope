<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove existing rows so NOT NULL constraint is safe to add
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('profiles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('gender')->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
