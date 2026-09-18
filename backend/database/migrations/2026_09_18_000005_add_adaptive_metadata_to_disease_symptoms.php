<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_symptoms', function (Blueprint $table) {
            $table->decimal('assessment_weight', 5, 2)->default(1);
            $table->boolean('is_key_symptom')->default(false);
            $table->decimal('absence_penalty', 5, 2)->default(0);
            $table->string('question_text', 500)->nullable();
            $table->text('evidence_source')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('disease_symptoms', function (Blueprint $table) {
            $table->dropColumn([
                'assessment_weight',
                'is_key_symptom',
                'absence_penalty',
                'question_text',
                'evidence_source',
            ]);
        });
    }
};
