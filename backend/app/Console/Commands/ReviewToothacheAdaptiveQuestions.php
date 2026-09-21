<?php

namespace App\Console\Commands;

use App\Models\AdaptiveQuestion;
use App\Models\MainSymptom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReviewToothacheAdaptiveQuestions extends Command
{
    protected $signature = 'adaptive:review-toothache-drafts';

    protected $description = 'Add source-reviewed wording and metadata to toothache question drafts';

    public function handle(): int
    {
        $toothache = MainSymptom::where('symptom_name', 'ปวดฟัน')->first();
        if (! $toothache) {
            $this->error('ไม่พบอาการปวดฟัน');

            return self::FAILURE;
        }

        $items = [
            [
                'targets' => ['คางบวม', 'คอบวม'],
                'question' => 'มีอาการบวมบริเวณคาง ใบหน้า หรือคอร่วมด้วยหรือไม่?',
                'detail' => 'ตรวจว่ามีอาการบวมลุกลามออกจากบริเวณฟันหรือเหงือกหรือไม่',
                'stage' => 'safety', 'required' => true,
                'source' => 'NHS Dental abscess: https://www.nhs.uk/conditions/dental-abscess/ ; MedlinePlus Tooth abscess: https://medlineplus.gov/ency/article/001060.htm',
            ],
            [
                'targets' => ['กลืนลำบาก'],
                'question' => 'มีอาการกลืนลำบาก พูดลำบาก หรือหายใจลำบากร่วมด้วยหรือไม่?',
                'detail' => 'เป็นคำถามสัญญาณสำคัญเกี่ยวกับการกลืน การพูด และการหายใจ',
                'stage' => 'safety', 'required' => true,
                'source' => 'NHS Dental abscess: https://www.nhs.uk/conditions/dental-abscess/ ; NHS Lothian Dental abscess: https://www.rightdecisions.scot.nhs.uk/antimicrobial-prescribing-nhs-lothian/body-systems/dental-infections/dental-abscess/',
            ],
            [
                'targets' => ['เลือดออกจากฟัน'],
                'question' => 'มีเลือดออกจากเหงือกหรือบริเวณรอบฟันที่ปวดหรือไม่?',
                'detail' => 'เลือดออกอาจมาจากเหงือกหรือการบาดเจ็บ ให้ตอบตามอาการที่สังเกตได้',
                'stage' => 'local', 'required' => false,
                'source' => 'American Dental Association, Bleeding Gums: https://www.mouthhealthy.org/all-topics-a-z/bleeding-gums',
            ],
            [
                'targets' => ['ฟันเหลืองดำ'],
                'question' => 'ฟันซี่ที่ปวดมีรอยคล้ำ สีน้ำตาล หรือสีดำผิดปกติหรือไม่?',
                'detail' => 'ประเมินเฉพาะการเปลี่ยนสีผิดปกติของฟันซี่ที่ปวด ไม่รวมสีเหลืองตามธรรมชาติ',
                'stage' => 'local', 'required' => false,
                'source' => 'MedlinePlus Tooth abnormal colors: https://medlineplus.gov/ency/article/003065.htm ; MedlinePlus Tooth Decay: https://medlineplus.gov/toothdecay.html',
            ],
        ];

        DB::transaction(function () use ($items, $toothache): void {
            foreach ($items as $priority => $item) {
                $targets = MainSymptom::whereIn('symptom_name', $item['targets'])->get();
                if ($targets->isEmpty()) {
                    continue;
                }
                $question = AdaptiveQuestion::where('question_symptom_id', $targets->first()->symptom_id)->firstOrFail();
                $question->update([
                    'question_text' => $item['question'],
                    'explanation_text' => $item['detail'],
                    'status' => 'reviewed',
                    'evidence_source' => $item['source'],
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
                $question->symptoms()->sync($targets->mapWithKeys(fn ($target, $index) => [
                    $target->symptom_id => ['display_order' => $index],
                ])->all());
                // Remove broad machine-generated routes. These reviewed questions
                // are intentionally scoped to the toothache assessment only.
                $question->rules()
                    ->where('initial_symptom_id', '!=', $toothache->symptom_id)
                    ->delete();
                $question->rules()->updateOrCreate(
                    ['initial_symptom_id' => $toothache->symptom_id],
                    [
                        'question_stage' => $item['stage'],
                        'priority' => $priority + 1,
                        'is_required' => $item['required'],
                        'status' => '1',
                    ],
                );
            }
        });

        $this->info('อัปเดตคำถามปวดฟัน 4 ข้อเป็น reviewed แล้ว');
        $this->warn('reviewed หมายถึงตรวจแหล่งอ้างอิงแล้ว แต่ยังไม่ใช่การอนุมัติทางคลินิก');

        return self::SUCCESS;
    }
}
