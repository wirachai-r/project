<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('symptom_search_logs');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('data_access_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_devices');
    }

    public function down(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_token', 255)->unique();
            $table->string('device_type', 10);
            $table->string('device_name', 100)->nullable();
            $table->char('status', 1)->default('1');
            $table->timestamp('last_active_at')->nullable();
            $table->char('user_id', 9);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'status']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100);
            $table->string('model_type', 100)->nullable();
            $table->string('model_id', 50)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->char('user_id', 9)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->index('action');
            $table->index('model_type');
            $table->index('created_at');
            $table->index('user_id');
        });

        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('data_type', 100);
            $table->char('data_owner_id', 9);
            $table->string('action', 20);
            $table->string('purpose', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->char('accessed_by', 9);
            $table->timestamps();

            $table->foreign('accessed_by')->references('user_id')->on('users')->cascadeOnDelete();
            $table->index('data_type');
            $table->index('data_owner_id');
            $table->index('accessed_by');
            $table->index('created_at');
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 9)->unique();
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->json('disabled_types')->nullable();
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });

        Schema::create('symptom_search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 255);
            $table->string('ip_address', 45)->nullable();
            $table->char('user_id', 9)->nullable();
            $table->char('symptom_id', 10)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('symptom_id')->references('symptom_id')->on('main_symptoms')->nullOnDelete();
            $table->index('keyword');
            $table->index('user_id');
            $table->index('symptom_id');
            $table->index('created_at');
        });
    }
};
