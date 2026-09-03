<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HealthTrendSummaryService
{
    private const ACTIONS = ['continue_follow_up', 'open_health_report'];

    public function __construct(private readonly AiClient $client) {}

    public function generate(array $healthData, array $period): array
    {
        $result = $this->client->generateStructured(
            'สรุปแนวโน้มจากข้อมูลที่ backend ส่งให้เท่านั้น แยกข้อมูลการประเมิน การติดตาม และบันทึกสุขภาพอย่างถูกต้อง ห้ามรวมคะแนนข้าม assessment ห้ามวินิจฉัย ยืนยันโรค ยืนยันว่าหายหรือปลอดภัย สั่งยา หรือสร้างข้อเท็จจริงใหม่ คำแนะนำดูแลตัวเองและสัญญาณที่ควรพบแพทย์ต้องถอดความจาก recommendation, self_care, when_to_see_doctor หรือ recommendations ที่ส่งให้เท่านั้น หากไม่มีข้อมูลอ้างอิงให้คืนรายการว่าง เรียก disease ว่าโรคหรือภาวะที่ผลประเมินระบุว่าอาจเกี่ยวข้อง ใช้เฉพาะ action ที่อนุญาต',
            ['period' => $period, 'health_data' => $healthData, 'allowed_actions' => self::ACTIONS],
            $this->schema(),
        );

        Validator::make($result, [
            'summary' => ['required', 'string', 'max:1500'],
            'observations' => ['required', 'array', 'max:5'],
            'observations.*' => ['string', 'max:300'],
            'self_care' => ['present', 'array', 'max:5'],
            'self_care.*' => ['string', 'max:500'],
            'warning_signs' => ['present', 'array', 'max:5'],
            'warning_signs.*' => ['string', 'max:500'],
            'allowed_actions' => ['required', 'array', 'max:2'],
            'allowed_actions.*' => [Rule::in(self::ACTIONS)],
            'disclaimer' => ['required', 'string', 'max:500'],
        ])->validate();

        return $result;
    }

    private function schema(): array
    {
        return ['name' => 'health_trend_summary', 'schema' => [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['summary', 'observations', 'self_care', 'warning_signs', 'allowed_actions', 'disclaimer'],
            'properties' => [
                'summary' => ['type' => 'string'],
                'observations' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'string']],
                'self_care' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'string']],
                'warning_signs' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'string']],
                'allowed_actions' => ['type' => 'array', 'maxItems' => 2, 'items' => ['type' => 'string', 'enum' => self::ACTIONS]],
                'disclaimer' => ['type' => 'string'],
            ],
        ]];
    }
}
