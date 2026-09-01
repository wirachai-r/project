<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HealthTrendSummaryService
{
    private const ACTIONS = ['continue_follow_up', 'open_health_report'];

    public function __construct(private readonly AiClient $client) {}

    public function generate(array $series, array $period): array
    {
        $result = $this->client->generateStructured(
            'สรุปแนวโน้มจากตัวเลขที่ backend ส่งให้เท่านั้น แยกแต่ละอาการ ห้ามรวมคะแนนข้าม assessment ห้ามวินิจฉัย ยืนยันว่าหายหรือปลอดภัย หรือสร้างตัวเลขใหม่ ใช้เฉพาะ action ที่อนุญาต',
            ['period' => $period, 'series' => $series, 'allowed_actions' => self::ACTIONS],
            $this->schema(),
        );

        Validator::make($result, [
            'summary' => ['required', 'string', 'max:1500'],
            'observations' => ['required', 'array', 'max:5'],
            'observations.*' => ['string', 'max:300'],
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
            'required' => ['summary', 'observations', 'allowed_actions', 'disclaimer'],
            'properties' => [
                'summary' => ['type' => 'string'],
                'observations' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'string']],
                'allowed_actions' => ['type' => 'array', 'maxItems' => 2, 'items' => ['type' => 'string', 'enum' => self::ACTIONS]],
                'disclaimer' => ['type' => 'string'],
            ],
        ]];
    }
}
