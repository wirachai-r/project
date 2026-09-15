<?php

namespace Database\Seeders;

use App\Models\FollowUpQuestionTemplate;
use App\Models\MainSymptom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FollowUpQuestionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Answer snapshots remain available after their old templates are removed.
            DB::table('symptom_follow_up_questions')->delete();
            FollowUpQuestionTemplate::query()->delete();

            $status = $this->template(
                'อาการนี้วันนี้เป็นอย่างไร?',
                'single_choice',
                ['หายแล้ว', 'ยังมี'],
                required: true,
                responseRules: [['value' => 'หายแล้ว', 'action' => 'prompt_end_tracking']],
            );
            $comparison = $this->template(
                'เมื่อเทียบกับครั้งก่อน อาการนี้เป็นอย่างไร?',
                'single_choice',
                ['ดีขึ้น', 'เท่าเดิม', 'แย่ลง'],
                responseRules: [['value' => 'ดีขึ้น', 'action' => 'prompt_end_tracking']],
            );
            $this->template(
                'มีอาการใหม่เกิดขึ้นหรือไม่?',
                'boolean',
                ['มี', 'ไม่มี'],
                required: true,
                global: true,
                responseRules: [['value' => true, 'action' => 'prompt_add_symptom']],
            );
            $pain = $this->template(
                'ระดับความปวดขณะนี้เท่าใด?',
                'scale',
                array_map('strval', range(0, 10)),
                '0 = ไม่ปวด, 10 = ปวดรุนแรงที่สุด',
                responseRules: [[
                    'operator' => 'equals',
                    'value' => '0',
                    'action' => 'prompt_end_tracking',
                ]],
            );
            $temperature = $this->template('อุณหภูมิร่างกายขณะนี้เท่าใด?', 'number', description: 'กรอกเมื่อสามารถวัดอุณหภูมิได้', unit: '°C');
            $nauseaFrequency = $this->template('วันนี้มีอาการคลื่นไส้หรืออาเจียนกี่ครั้ง?', 'number', description: 'กรอกจำนวนครั้งโดยประมาณ', unit: 'ครั้ง');
            $balance = $this->template('เดินหรือยืนได้ตามปกติหรือไม่?', 'boolean', ['ได้ตามปกติ', 'ไม่ได้ตามปกติ']);

            foreach (MainSymptom::query()->get(['symptom_id', 'symptom_name']) as $symptom) {
                $sequence = 1;
                $isPain = str_contains($symptom->symptom_name, 'ปวด');
                if ($isPain) {
                    $this->attach($symptom->symptom_id, $pain->id, $sequence++);
                    $this->attach($symptom->symptom_id, $comparison->id, $sequence++);
                } else {
                    $this->attach($symptom->symptom_id, $status->id, $sequence++);
                }
                if (str_contains($symptom->symptom_name, 'ไข้') || str_contains($symptom->symptom_name, 'ตัวร้อน')) {
                    $this->attach($symptom->symptom_id, $temperature->id, $sequence++);
                }
                if (str_contains($symptom->symptom_name, 'คลื่นไส้') || str_contains($symptom->symptom_name, 'อาเจียน')) {
                    $this->attach($symptom->symptom_id, $nauseaFrequency->id, $sequence++);
                }
                if (str_contains($symptom->symptom_name, 'เวียน')) {
                    $this->attach($symptom->symptom_id, $balance->id, $sequence++);
                }
            }
        });
    }

    private function template(string $question, string $type, ?array $options = null, ?string $description = null, ?string $unit = null, bool $required = false, bool $global = false, ?array $responseRules = null): FollowUpQuestionTemplate
    {
        return FollowUpQuestionTemplate::query()->create([
            'question_text' => $question,
            'description' => $description,
            'answer_type' => $type,
            'options' => $options,
            'unit' => $unit,
            'response_rules' => $responseRules,
            'is_required' => $required,
            'applies_to_all_symptoms' => $global,
            'status' => '1',
        ]);
    }

    private function attach(string $symptomId, int $templateId, int $sequence): void
    {
        DB::table('symptom_follow_up_questions')->insert([
            'symptom_id' => $symptomId,
            'question_template_id' => $templateId,
            'sequence' => $sequence,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
