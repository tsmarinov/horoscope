<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deleted_accounts', function (Blueprint $table) {
            $table->string('event')->default('deleted')->after('id'); // registered | email_changed | deleted
            $table->json('meta')->nullable()->after('deleted_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_email')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('deleted_accounts', function (Blueprint $table) {
            $table->dropColumn(['event', 'meta']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pending_email');
        });
    }
};
