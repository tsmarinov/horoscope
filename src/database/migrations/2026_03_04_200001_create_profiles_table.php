<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->date('birth_date')->nullable();
            $table->time('birth_time')->nullable();
            $table->boolean('birth_time_approximate')->default(false);
            $table->unsignedBigInteger('birth_city_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('birth_city_id')->references('id')->on('cities')->nullOnDelete();
        });

        Schema::create('guest_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_session_id')->unique();
            $table->string('name')->nullable();
            $table->date('birth_date')->nullable();
            $table->time('birth_time')->nullable();
            $table->boolean('birth_time_approximate')->default(false);
            $table->unsignedBigInteger('birth_city_id')->nullable();
            $table->timestamps();

            $table->foreign('guest_session_id')->references('id')->on('guest_sessions')->cascadeOnDelete();
            $table->foreign('birth_city_id')->references('id')->on('cities')->nullOnDelete();
        });

        Schema::create('demo_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->date('birth_date');
            $table->time('birth_time')->nullable();
            $table->boolean('birth_time_approximate')->default(false);
            $table->unsignedBigInteger('birth_city_id')->nullable();
            $table->timestamps();

            $table->foreign('birth_city_id')->references('id')->on('cities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_profiles');
        Schema::dropIfExists('guest_profiles');
        Schema::dropIfExists('user_profiles');
    }
};
