<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('assessment_mode', 16)->default('classic');
        });

        Schema::create('adaptive_assessments', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 9)->nullable()->index();
            $table->string('session_token', 64)->nullable()->index();
            $table->char('initial_symptom_id', 10);
            $table->string('status', 16)->default('processing')->index();
            $table->unsignedTinyInteger('question_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('initial_symptom_id')->references('symptom_id')->on('main_symptoms')->restrictOnDelete();
        });

        Schema::create('adaptive_assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adaptive_assessment_id')->constrained()->cascadeOnDelete();
            $table->char('symptom_id', 10);
            $table->string('answer', 12);
            $table->timestamps();

            $table->foreign('symptom_id')->references('symptom_id')->on('main_symptoms')->restrictOnDelete();
            $table->unique(['adaptive_assessment_id', 'symptom_id'], 'adaptive_answer_unique');
        });

        Schema::create('adaptive_assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adaptive_assessment_id')->constrained()->cascadeOnDelete();
            $table->char('disease_id', 10)->nullable();
            $table->string('disease_name');
            $table->unsignedTinyInteger('match_percent')->default(0);
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('disease_id')->references('disease_id')->on('diseases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adaptive_assessment_results');
        Schema::dropIfExists('adaptive_assessment_answers');
        Schema::dropIfExists('adaptive_assessments');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('assessment_mode'));
    }
};
