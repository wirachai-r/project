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
        Schema::create('main_symptoms', function (Blueprint $table) {
            $table->char('symptom_id', 10)->primary();
            $table->string('symptom_name', 150);
            $table->string('symptom_name_en', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('symptom_image', 255)->nullable();
            $table->char('status', 1)->default('1'); // 1=Active, 2=Inactive

            // FK
            $table->char('symptom_category_id', 6);
            $table->foreign('symptom_category_id')
                ->references('symptom_category_id')
                ->on('symptom_categories')
                ->restrictOnDelete();

            // FK
            $table->char('created_by', 9)->nullable();
            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();

            // FK
            $table->char('updated_by', 9)->nullable();
            $table->foreign('updated_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('status');
            $table->index('symptom_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_symptoms');
    }
};
