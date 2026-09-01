<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use the composite unique index to support the user_id
        // foreign key. Give that foreign key its own index before removing it.
        Schema::table('daily_health_records', function (Blueprint $table) {
            $table->index('user_id', 'daily_health_records_user_id_index');
        });

        Schema::table('daily_health_records', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'recorded_on']);
            $table->index(['user_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::table('daily_health_records', function (Blueprint $table) {
            $table->unique(['user_id', 'recorded_on']);
            $table->dropIndex(['user_id', 'recorded_on']);
            $table->dropIndex('daily_health_records_user_id_index');
        });
    }
};
