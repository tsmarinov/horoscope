<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sun_sign_horoscopes', function (Blueprint $table) {
            $table->id();
            $table->string('sign', 20);
            $table->date('date');
            $table->text('body');
            $table->timestamps();

            $table->unique(['sign', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sun_sign_horoscopes');
    }
};
