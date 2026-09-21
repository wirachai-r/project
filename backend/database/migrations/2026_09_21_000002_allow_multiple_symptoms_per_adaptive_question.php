<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adaptive_question_symptoms', function (Blueprint $table) {
            $table->foreignId('adaptive_question_id')->constrained()->cascadeOnDelete();
            $table->char('symptom_id', 10);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('symptom_id')->references('symptom_id')->on('main_symptoms')->restrictOnDelete();
            $table->primary(['adaptive_question_id', 'symptom_id'], 'adaptive_question_symptoms_primary');
        });

        DB::table('adaptive_questions')->orderBy('id')->each(function ($question): void {
            DB::table('adaptive_question_symptoms')->insert([
                'adaptive_question_id' => $question->id,
                'symptom_id' => $question->question_symptom_id,
                'display_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adaptive_question_symptoms');
    }
};
