<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follow_up_entries', function (Blueprint $table) {
            $table->unsignedTinyInteger('severity')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('follow_up_entries')->whereNull('severity')->update(['severity' => 1]);
        Schema::table('follow_up_entries', function (Blueprint $table) {
            $table->unsignedTinyInteger('severity')->nullable(false)->change();
        });
    }
};
