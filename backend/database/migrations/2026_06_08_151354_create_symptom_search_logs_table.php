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
        Schema::create('symptom_search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 255);
            $table->string('ip_address', 45)->nullable();

            // FK
            $table->char('user_id', 9)->nullable();
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->char('symptom_id', 10)->nullable();
            $table->foreign('symptom_id')
                ->references('symptom_id')
                ->on('main_symptoms')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('keyword');
            $table->index('user_id');
            $table->index('symptom_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('symptom_search_logs');
    }
};
