<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_reminders', function (Blueprint $table) {
            $table->foreignId('health_episode_id')->nullable()->after('user_id')
                ->constrained('health_episodes')->cascadeOnDelete();
            $table->unique(['user_id', 'health_episode_id', 'reminder_type'], 'health_reminders_episode_unique');
        });
    }

    public function down(): void
    {
        Schema::table('health_reminders', function (Blueprint $table) {
            $table->dropUnique('health_reminders_episode_unique');
            $table->dropConstrainedForeignId('health_episode_id');
        });
    }
};
