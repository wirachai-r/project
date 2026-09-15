<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setRules(
            ['อาการนี้วันนี้เป็นอย่างไร?'],
            [['value' => 'หายแล้ว', 'action' => 'prompt_end_tracking']],
        );

        $this->setRules(
            [
                'เมื่อเทียบกับครั้งก่อน อาการนี้เป็นอย่างไร?',
                'เมื่อเทียบกับครั้งก่อน อาการเป็นอย่างไร?',
            ],
            [['value' => 'ดีขึ้น', 'action' => 'prompt_end_tracking']],
        );

        $this->setRules(
            ['มีอาการใหม่เกิดขึ้นหรือไม่?'],
            [['value' => true, 'action' => 'prompt_add_symptom']],
        );
    }

    public function down(): void
    {
        DB::table('follow_up_question_templates')
            ->whereIn('question_text', [
                'อาการนี้วันนี้เป็นอย่างไร?',
                'เมื่อเทียบกับครั้งก่อน อาการนี้เป็นอย่างไร?',
                'เมื่อเทียบกับครั้งก่อน อาการเป็นอย่างไร?',
                'มีอาการใหม่เกิดขึ้นหรือไม่?',
            ])
            ->update(['response_rules' => null]);
    }

    private function setRules(array $questions, array $rules): void
    {
        DB::table('follow_up_question_templates')
            ->whereIn('question_text', $questions)
            ->whereNull('response_rules')
            ->update([
                'response_rules' => json_encode($rules, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }
};
