<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adaptive_questions', function (Blueprint $table) {
            $table->id();
            $table->char('question_symptom_id', 10);
            $table->string('question_text', 500);
            $table->text('explanation_text')->nullable();
            $table->string('answer_type', 24);
            $table->string('status', 16)->default('draft')->index();
            $table->text('evidence_source')->nullable();
            $table->char('approved_by', 9)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('question_symptom_id')->references('symptom_id')->on('main_symptoms')->restrictOnDelete();
            $table->foreign('approved_by')->references('user_id')->on('users')->nullOnDelete();
        });

        Schema::create('adaptive_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adaptive_question_id')->constrained()->cascadeOnDelete();
            $table->string('option_text', 200);
            $table->string('option_value', 80);
            $table->char('target_symptom_id', 10)->nullable();
            $table->string('answer_effect', 16);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->char('status', 1)->default('1');
            $table->timestamps();

            $table->foreign('target_symptom_id')->references('symptom_id')->on('main_symptoms')->restrictOnDelete();
            $table->unique(['adaptive_question_id', 'option_value'], 'adaptive_question_option_value_unique');
        });

        Schema::create('adaptive_question_rules', function (Blueprint $table) {
            $table->id();
            $table->char('initial_symptom_id', 10);
            $table->foreignId('adaptive_question_id')->constrained()->cascadeOnDelete();
            $table->string('question_stage', 16);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_required')->default(false);
            $table->char('status', 1)->default('1');
            $table->timestamps();

            $table->foreign('initial_symptom_id')->references('symptom_id')->on('main_symptoms')->cascadeOnDelete();
            $table->unique(['initial_symptom_id', 'adaptive_question_id'], 'adaptive_question_rule_unique');
            $table->index(['initial_symptom_id', 'status', 'question_stage', 'priority'], 'adaptive_question_rule_lookup');
        });

        Schema::table('adaptive_assessment_answers', function (Blueprint $table) {
            $table->dropUnique('adaptive_answer_unique');
            $table->foreignId('adaptive_question_id')->nullable()->after('adaptive_assessment_id')->constrained()->nullOnDelete();
            $table->json('answer_payload')->nullable()->after('answer');
            $table->unique(['adaptive_assessment_id', 'adaptive_question_id'], 'adaptive_answer_question_unique');
        });
    }

    public function down(): void
    {
        Schema::table('adaptive_assessment_answers', function (Blueprint $table) {
            $table->dropUnique('adaptive_answer_question_unique');
            $table->dropConstrainedForeignId('adaptive_question_id');
            $table->dropColumn('answer_payload');
            $table->unique(['adaptive_assessment_id', 'symptom_id'], 'adaptive_answer_unique');
        });
        Schema::dropIfExists('adaptive_question_rules');
        Schema::dropIfExists('adaptive_question_options');
        Schema::dropIfExists('adaptive_questions');
    }
};
