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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->char('assessment_status', 1)->default('P'); // P=Processing, C=Completed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // FK
            $table->char('user_id', 9);
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();

            $table->char('symptom_id', 10);
            $table->foreign('symptom_id')
                ->references('symptom_id')
                ->on('main_symptoms')
                ->restrictOnDelete();

            $table->char('diagram_id', 5);
            $table->foreign('diagram_id')
                ->references('diagram_id')
                ->on('diagrams')
                ->restrictOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('user_id');
            $table->index('assessment_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
