<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_clarification_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('box_id', 10);
            $table->string('status', 20)->default('active');
            $table->string('resolved_to', 20)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->foreign('box_id')->references('box_id')->on('question_boxes')->cascadeOnDelete();
            $table->unique(['assessment_id', 'box_id']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('ai_clarification_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_clarification_sessions')->cascadeOnDelete();
            $table->text('question_text');
            $table->text('explanation')->nullable();
            $table->string('source', 20)->default('ai');
            $table->unsignedTinyInteger('sequence');
            $table->timestamps();
            $table->unique(['session_id', 'sequence']);
        });

        Schema::create('ai_clarification_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('ai_clarification_questions')->cascadeOnDelete();
            $table->string('external_id', 80);
            $table->string('choice_text', 300);
            $table->string('maps_to', 30);
            $table->string('maps_to_choice_id', 10)->nullable();
            $table->unsignedTinyInteger('sequence');
            $table->timestamps();
            $table->foreign('maps_to_choice_id')->references('choice_id')->on('answer_choices')->nullOnDelete();
            $table->unique(['question_id', 'external_id']);
            $table->unique(['question_id', 'sequence']);
        });

        Schema::create('ai_clarification_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->unique()->constrained('ai_clarification_questions')->cascadeOnDelete();
            $table->foreignId('choice_id')->constrained('ai_clarification_choices')->cascadeOnDelete();
            $table->timestamp('answered_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_clarification_answers');
        Schema::dropIfExists('ai_clarification_choices');
        Schema::dropIfExists('ai_clarification_questions');
        Schema::dropIfExists('ai_clarification_sessions');
    }
};
