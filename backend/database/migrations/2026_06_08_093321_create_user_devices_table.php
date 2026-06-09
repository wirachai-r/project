<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_token', 255)->unique();  // token จาก FCM
            $table->string('device_type', 10);              // ios, android, web
            $table->string('device_name', 100)->nullable(); // เช่น "iPhone 14", "Samsung S23"
            $table->char('status', 1)->default('1');        // 1=active, 2=inactive
            $table->timestamp('last_active_at')->nullable();

            // FK
            $table->char('user_id', 9);
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->cascadeOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
