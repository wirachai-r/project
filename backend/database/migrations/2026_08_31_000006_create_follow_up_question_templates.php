<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('follow_up_question_templates')) {
            Schema::create('follow_up_question_templates', function (Blueprint $table) {
                $table->id();
                $table->string('question_text', 500);
                $table->string('description', 1000)->nullable();
                $table->string('answer_type', 30);
                $table->json('options')->nullable();
                $table->string('unit', 30)->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('applies_to_all_symptoms')->default(false);
                $table->char('status', 1)->default('1');
                $table->timestamps();
                $table->index(['status', 'applies_to_all_symptoms'], 'fu_question_templates_status_all_idx');
            });
        }

        if (! Schema::hasTable('symptom_follow_up_questions')) {
            Schema::create('symptom_follow_up_questions', function (Blueprint $table) {
                $table->id();
                $table->string('symptom_id', 10);
                $table->foreignId('question_template_id')->constrained('follow_up_question_templates')->cascadeOnDelete();
                $table->unsignedSmallInteger('sequence')->default(1);
                $table->boolean('is_required_override')->nullable();
                $table->char('status', 1)->default('1');
                $table->timestamps();
                $table->foreign('symptom_id')->references('symptom_id')->on('main_symptoms')->cascadeOnDelete();
                $table->unique(['symptom_id', 'question_template_id'], 'sym_fu_questions_symptom_template_uq');
                $table->index(['symptom_id', 'status', 'sequence']);
            });
        }

        if (! Schema::hasTable('follow_up_entry_answers')) {
            Schema::create('follow_up_entry_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('follow_up_entry_id')->constrained()->cascadeOnDelete();
                $table->foreignId('question_template_id')->nullable()->constrained('follow_up_question_templates')->nullOnDelete();
                $table->string('question_text_snapshot', 500);
                $table->string('answer_type_snapshot', 30);
                $table->json('answer_value');
                $table->timestamp('answered_at');
                $table->timestamps();
                $table->unique(['follow_up_entry_id', 'question_template_id'], 'fu_entry_answers_entry_template_uq');
            });
        }

        $now = now();
        $templates = [
            [
                'question_text' => 'เมื่อเทียบกับครั้งก่อน อาการเป็นอย่างไร?',
                'description' => 'เลือกแนวโน้มที่ใกล้กับอาการในตอนนี้มากที่สุด',
                'answer_type' => 'single_choice',
                'options' => json_encode(['ดีขึ้น', 'ใกล้เคียงเดิม', 'แย่ลง'], JSON_UNESCAPED_UNICODE),
                'is_required' => true,
                'applies_to_all_symptoms' => true,
                'status' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question_text' => 'มีอาการใหม่เกิดขึ้นหรือไม่?',
                'description' => 'หากมี สามารถเพิ่มอาการใหม่เข้าสู่การติดตามได้หลังบันทึก',
                'answer_type' => 'boolean',
                'options' => json_encode(['มี', 'ไม่มี'], JSON_UNESCAPED_UNICODE),
                'is_required' => true,
                'applies_to_all_symptoms' => true,
                'status' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('follow_up_question_templates')->updateOrInsert(
                ['question_text' => $template['question_text']],
                $template,
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_entry_answers');
        Schema::dropIfExists('symptom_follow_up_questions');
        Schema::dropIfExists('follow_up_question_templates');
    }
};
