<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->foreignId('solar_return_city_id')
                  ->nullable()
                  ->after('birth_city_id')
                  ->constrained('cities')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\City::class, 'solar_return_city_id');
            $table->dropColumn('solar_return_city_id');
        });
    }
};
