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
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->char('urgency_level', 1); // R=Red, O=Orange, Y=Yellow, G=Green
            $table->char('should_see_doctor', 1)->default('N'); // Y=Yes, N=No
            $table->text('recommendation')->nullable();

            // FK
            $table->unsignedBigInteger('assessment_id');
            $table->foreign('assessment_id')
                ->references('id')
                ->on('assessments')
                ->cascadeOnDelete();

            $table->char('rule_id', 10);
            $table->foreign('rule_id')
                ->references('rule_id')
                ->on('diagnosis_rules')
                ->restrictOnDelete();

            $table->char('disease_id', 10);
            $table->foreign('disease_id')
                ->references('disease_id')
                ->on('diseases')
                ->restrictOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('assessment_id');
            $table->index('urgency_level');
            $table->index('disease_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};
