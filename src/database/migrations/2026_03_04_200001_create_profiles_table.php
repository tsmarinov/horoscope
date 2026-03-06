<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            // Exactly one of user_id / guest_id is set, or neither (is_demo = true)
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->unsignedBigInteger('guest_id')->nullable()->unique();
            $table->boolean('is_demo')->default(false);

            // Required for demo profiles; optional for registered users (display name)
            $table->string('name')->nullable();

            // URL slug — demo profiles only
            $table->string('slug')->nullable()->unique();

            $table->date('birth_date')->nullable();
            $table->time('birth_time')->nullable();
            $table->boolean('birth_time_approximate')->default(false);
            $table->unsignedBigInteger('birth_city_id')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('guest_id')->references('id')->on('guests')->cascadeOnDelete();
            $table->foreign('birth_city_id')->references('id')->on('cities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
