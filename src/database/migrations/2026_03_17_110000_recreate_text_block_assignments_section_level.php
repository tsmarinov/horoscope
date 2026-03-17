<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::drop('text_block_assignments');

        Schema::create('text_block_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id');
            $table->string('section', 100);
            $table->smallInteger('variant')->default(1);
            $table->timestamps();

            $table->unique(['profile_id', 'section']);
            $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
            $table->index('section');
        });
    }

    public function down(): void
    {
        // No rollback — table was empty when dropped
    }
};
