<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_horoscope_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_uuid')->nullable();
            $table->string('user_email')->nullable();
            $table->string('profile_uuid');
            $table->json('profile_snapshot');
            $table->string('type');
            $table->boolean('premium_content')->default(false);
            $table->timestamp('premium_requested_at')->nullable();
            $table->timestamps();

            $table->index('user_uuid');
            $table->index('profile_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_horoscope_logs');
    }
};
