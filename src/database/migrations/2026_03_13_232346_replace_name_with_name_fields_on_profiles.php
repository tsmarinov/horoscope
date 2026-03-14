<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Copy existing name → first_name where first_name is still null
        DB::table('profiles')
            ->whereNull('first_name')
            ->whereNotNull('name')
            ->update(['first_name' => DB::raw('name')]);

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('name')->nullable()->after('is_demo');
            $table->string('first_name')->nullable()->change();
        });

        // Restore name from first_name + last_name
        DB::table('profiles')->update([
            'name' => DB::raw("TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))"),
        ]);
    }
};
