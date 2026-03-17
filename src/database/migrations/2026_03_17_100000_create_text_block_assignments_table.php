<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('text_block_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id');
            $table->string('section', 100);
            $table->string('key', 255);
            $table->smallInteger('variant')->default(1);
            $table->timestamps();

            $table->unique(['profile_id', 'section', 'key']);
            $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
            $table->index(['section', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_block_assignments');
    }
};
