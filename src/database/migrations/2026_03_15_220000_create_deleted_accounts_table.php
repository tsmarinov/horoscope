<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deleted_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamp('registered_at');
            $table->timestamp('deleted_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('unsubscribed_at')->nullable()->after('accepts_marketing');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_accounts');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('unsubscribed_at');
        });
    }
};
