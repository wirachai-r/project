<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_reminders', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 9);
            $table->string('title', 100);
            $table->string('reminder_type', 30);
            $table->string('frequency', 10)->default('daily');
            $table->time('time_of_day');
            $table->json('days_of_week')->nullable();
            $table->string('timezone', 50)->default('Asia/Bangkok');
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_reminders');
    }
};
