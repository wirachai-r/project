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
        Schema::create('rule_conditions', function (Blueprint $table) {
            $table->char('condition_id', 10)->primary();
            $table->char('status', 1)->default('1'); // 1=Active, 2=Inactive

            // FK
            $table->char('rule_id', 10);
            $table->foreign('rule_id')
                ->references('rule_id')
                ->on('diagnosis_rules')
                ->cascadeOnDelete();

            $table->char('box_id', 10);
            $table->foreign('box_id')
                ->references('box_id')
                ->on('question_boxes')
                ->restrictOnDelete();

            $table->char('choice_id', 10);
            $table->foreign('choice_id')
                ->references('choice_id')
                ->on('answer_choices')
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
            $table->index('rule_id');
            $table->index('box_id');
            $table->index('choice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_conditions');
    }
};
