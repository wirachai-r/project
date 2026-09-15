<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class HealthTrendSummaryService
{
    public const VERSION = 8;

    private const ACTIONS = ['continue_follow_up', 'open_health_report'];

    public function __construct(private readonly AiClient $client) {}

    public function generate(array $healthData, array $period): array
    {
        $compactData = $this->compact($healthData);
        try {
            $result = $this->client->generateStructured(
                'สรุปแนวโน้มสุขภาพเป็นภาษาไทยง่าย กระชับ และอ้างอิงเฉพาะข้อมูลที่ backend ส่งให้ '
                .'เริ่มด้วยภาพรวมของช่วงเวลาที่เลือก แล้วแยกข้อสังเกตที่มีหลักฐานจากการประเมิน การติดตามอาการ และบันทึกสุขภาพรายวันอย่างถูกต้อง '
                .'ใช้ dashboard_context ซึ่งเป็นข้อมูลสรุปชุดเดียวกับหน้าแนวโน้มประกอบ และต้องไม่รายงานจำนวนที่ขัดกับข้อมูลส่วนนี้ '
                .'เปรียบเทียบค่าความรุนแรงเฉพาะภายใน follow_up_series เดียวกัน ห้ามรวมคะแนนข้ามอาการหรือข้าม assessment '
                .'หากข้อมูลมีเพียงครั้งเดียวหรือไม่พอเปรียบเทียบ ให้บอกตรง ๆ ว่ายังสรุปการเปลี่ยนแปลงไม่ได้ '
                .'หลีกเลี่ยงการลิสต์ชื่ออาการดิบทั้งหมดและห้ามกล่าวข้อมูลเดิมซ้ำทั้ง summary และ observations '
                .'เมื่อข้อมูลยังน้อย ให้สรุปสั้น ๆ ว่าพบข้อมูลประเภทใดและต้องบันทึกเพิ่มจึงจะเปรียบเทียบได้ '
                .'ห้ามวินิจฉัย ยืนยันโรค ยืนยันว่าหายหรือปลอดภัย สั่งยา หรือสร้างข้อเท็จจริงใหม่ '
                .'สร้าง self_care โดยใช้ข้อมูลครบทั้งสามส่วน ได้แก่ assessments, follow_up_series และ daily_records '
                .'สำหรับ assessments และ care_context ให้ถอดความคำแนะนำจาก possible_conditions.self_care หรือ care_context.self_care และเลือกเฉพาะรายการที่สัมพันธ์กับข้อมูลในช่วงที่เลือก '
                .'สำหรับ follow_up_series และ daily_records ให้เสนอได้เฉพาะแนวทางทั่วไปที่สัมพันธ์กับข้อมูลจริง เช่น การบันทึกอาการเดิมอย่างต่อเนื่อง การสังเกตการเปลี่ยนแปลง และการเพิ่มรายละเอียดในการบันทึก '
                .'ห้ามสร้างคำแนะนำเรื่องยา การรักษา อาหาร ปริมาณการดื่มน้ำ การพักผ่อน หรือเกณฑ์ความรุนแรงขึ้นเอง และห้ามนำ recommendation ซึ่งอาจเป็นคำแนะนำเร่งด่วนมาใส่ '
                .'warning_signs ต้องถอดความจาก possible_conditions.when_to_see_doctor หรือ care_context.when_to_see_doctor เท่านั้น หากไม่มีแหล่งข้อมูลให้คืนรายการว่าง '
                .'เรียก disease ว่าโรคหรือภาวะที่ผลประเมินระบุว่าอาจเกี่ยวข้อง และใช้เฉพาะ action ที่อนุญาต'
                .CoreAnalysisRules::instructions()
                .ServiceAnalysisRules::healthTrend(),
                ['period' => $period, 'health_data' => $compactData, 'allowed_actions' => self::ACTIONS],
                $this->schema(),
            );
            $result = $this->normalize($result);
            $result['self_care'] = collect($result['self_care'])
                ->concat($this->trackingRecommendations($compactData))
                ->filter()
                ->unique()
                ->take(5)
                ->values()
                ->all();
            $this->validate($result);
            $result['source'] = 'ai';

            return $result;
        } catch (Throwable $exception) {
            Log::warning('AI health trend summary failed; using backend fallback.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback($compactData, $period);
        }
    }

    private function validate(array $result): void
    {
        Validator::make($result, [
            'summary' => ['required', 'string', 'max:1500'],
            'observations' => ['present', 'array', 'max:5'],
            'observations.*' => ['string', 'max:300'],
            'self_care' => ['present', 'array', 'max:5'],
            'self_care.*' => ['string', 'max:500'],
            'warning_signs' => ['present', 'array', 'max:5'],
            'warning_signs.*' => ['string', 'max:500'],
            'allowed_actions' => ['required', 'array', 'max:2'],
            'allowed_actions.*' => [Rule::in(self::ACTIONS)],
            'disclaimer' => ['required', 'string', 'max:500'],
        ])->validate();
    }

    private function normalize(array $result): array
    {
        foreach (['observations', 'self_care', 'warning_signs'] as $field) {
            if (! array_key_exists($field, $result)) {
                $result[$field] = [];
            }
        }

        if (! array_key_exists('allowed_actions', $result)) {
            $result['allowed_actions'] = self::ACTIONS;
        }

        if (! array_key_exists('disclaimer', $result)) {
            $result['disclaimer'] = 'สรุปนี้สร้างจากข้อมูลที่บันทึกไว้ในระบบ ไม่ใช่การวินิจฉัยทางการแพทย์';
        }

        return $result;
    }

    private function compact(array $healthData): array
    {
        $followUps = collect($healthData['follow_up_series'] ?? [])->take(20)->map(function (array $series) {
            $records = collect($series['records'] ?? []);
            $series['records_total'] = $records->count();
            $series['records'] = $records->take(-30)->values()->all();

            return $series;
        })->values()->all();
        $assessments = collect($healthData['assessments'] ?? [])->map(fn (array $assessment) => [
            'symptom_name' => $assessment['symptom_name'] ?? null,
            'completed_at' => $assessment['completed_at'] ?? null,
            'possible_conditions' => collect($assessment['results'] ?? [])->flatMap(
                fn (array $result) => collect($result['possible_conditions'] ?? [])->map(fn (array $condition) => [
                    'name' => $condition['name'] ?? null,
                    'self_care' => $condition['self_care'] ?? null,
                    'when_to_see_doctor' => $condition['when_to_see_doctor'] ?? null,
                ])
            )->values()->all(),
        ]);
        $dailyRecords = collect($healthData['daily_records'] ?? []);

        return [
            'counts' => [
                'follow_up_series' => count($healthData['follow_up_series'] ?? []),
                'assessments' => $assessments->count(),
                'daily_records' => $dailyRecords->count(),
            ],
            'follow_up_series' => $followUps,
            'assessments' => $assessments->take(30)->values()->all(),
            'daily_records' => $dailyRecords->take(-90)->values()->all(),
            'care_context' => collect($healthData['care_context'] ?? [])->take(20)->values()->all(),
            'dashboard_context' => $healthData['dashboard_context'] ?? [],
        ];
    }

    private function fallback(array $healthData, array $period): array
    {
        $counts = $healthData['counts'];
        $observations = collect($healthData['follow_up_series'])->take(3)->map(function (array $series) {
            $name = $series['symptom_name'] ?: 'อาการที่ติดตาม';
            if (($series['record_count'] ?? 0) < 2) {
                return "มีข้อมูล{$name}เพียงครั้งเดียว จึงยังเปรียบเทียบการเปลี่ยนแปลงไม่ได้";
            }

            return "ระดับอาการ{$name}เปลี่ยนจาก {$series['first_severity']} เป็น {$series['latest_severity']} จากข้อมูลที่บันทึกไว้";
        });
        if ($observations->isEmpty()) {
            $observations->push('ยังไม่มีข้อมูลติดตามอาการอย่างน้อย 2 ครั้งสำหรับเปรียบเทียบการเปลี่ยนแปลง');
        }

        $conditions = collect($healthData['assessments'])->flatMap(
            fn (array $assessment) => $assessment['possible_conditions'] ?? []
        )->concat($healthData['care_context'] ?? []);
        $selfCare = $conditions->pluck('self_care')
            ->filter()->unique()->take(5)->values()->all();
        $selfCare = collect($selfCare);
        $selfCare = $selfCare->concat($this->trackingRecommendations($healthData));
        $selfCare = $selfCare->unique()->take(5)->values()->all();
        $warningSigns = $conditions->pluck('when_to_see_doctor')->filter()->unique()->take(5)->values()->all();

        return [
            'summary' => "ช่วง {$period['from']} ถึง {$period['to']} มีผลประเมิน {$counts['assessments']} ครั้ง บันทึกสุขภาพ {$counts['daily_records']} รายการ และอาการที่ติดตาม {$counts['follow_up_series']} รายการ",
            'observations' => $observations->values()->all(),
            'self_care' => $selfCare,
            'warning_signs' => $warningSigns,
            'allowed_actions' => self::ACTIONS,
            'disclaimer' => 'สรุปนี้สร้างจากข้อมูลที่บันทึกไว้ในระบบ ไม่ใช่การวินิจฉัยทางการแพทย์',
            'source' => 'backend_fallback',
        ];
    }

    private function trackingRecommendations(array $healthData): array
    {
        $recommendations = [];
        if (! empty($healthData['follow_up_series'])) {
            $recommendations[] = 'ติดตามและบันทึกระดับอาการเดิมอย่างต่อเนื่อง เพื่อให้เปรียบเทียบการเปลี่ยนแปลงได้ชัดเจนขึ้น';
        }
        if (! empty($healthData['daily_records'])) {
            $recommendations[] = 'บันทึกสุขภาพรายวันพร้อมรายละเอียดอาการที่สังเกตได้ เพื่อช่วยให้เห็นรูปแบบของอาการได้ชัดเจนขึ้น';
        }

        return $recommendations;
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
