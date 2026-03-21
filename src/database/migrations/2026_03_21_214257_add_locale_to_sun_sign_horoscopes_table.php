<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sun_sign_horoscopes', function (Blueprint $table) {
            $table->string('locale', 10)->default('en')->after('date');
            $table->dropUnique(['sign', 'date']);
            $table->unique(['sign', 'date', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::table('sun_sign_horoscopes', function (Blueprint $table) {
            $table->dropUnique(['sign', 'date', 'locale']);
            $table->dropColumn('locale');
            $table->unique(['sign', 'date']);
        });
    }
};
