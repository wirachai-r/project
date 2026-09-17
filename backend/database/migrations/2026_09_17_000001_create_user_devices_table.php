<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 9);
            $table->string('device_token', 512)->unique();
            $table->string('device_type', 20);
            $table->string('device_name', 100)->nullable();
            $table->char('status', 1)->default('1');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
