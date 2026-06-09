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
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();

            // FK
            $table->unsignedBigInteger('assessment_id');
            $table->foreign('assessment_id')
                ->references('id')
                ->on('assessments')
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

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('assessment_id');
            $table->index('box_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
