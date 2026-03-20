<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekday_texts', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('iso_day')->unsigned(); // 1=Mon … 7=Sun
            $table->string('language', 8)->default('en');
            $table->string('name', 50);
            $table->string('colors', 100);
            $table->string('gem', 50);
            $table->string('theme', 100);
            $table->text('description');
            $table->unique(['iso_day', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekday_texts');
    }
};
