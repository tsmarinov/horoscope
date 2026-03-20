<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('profiles', 'uuid')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }

        // Back-fill existing rows
        DB::table('profiles')->whereNull('uuid')->orderBy('id')->each(function ($row) {
            DB::table('profiles')
                ->where('id', $row->id)
                ->update(['uuid' => \Illuminate\Support\Str::uuid()->toString()]);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
