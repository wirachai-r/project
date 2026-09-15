<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templateIds = DB::table('follow_up_question_templates')
            ->where('question_text', 'ระดับความปวดขณะนี้เท่าใด?')
            ->get(['id', 'response_rules'])
            ->filter(function ($template) {
                $rules = $template->response_rules === null
                    ? null
                    : json_decode($template->response_rules, true);

                return $rules === null || $rules === [];
            })
            ->pluck('id');

        DB::table('follow_up_question_templates')
            ->whereIn('id', $templateIds)
            ->update([
                'response_rules' => json_encode([[
                    'operator' => 'equals',
                    'value' => '0',
                    'action' => 'prompt_end_tracking',
                ]], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $expectedRules = [[
            'operator' => 'equals',
            'value' => '0',
            'action' => 'prompt_end_tracking',
        ]];
        $templateIds = DB::table('follow_up_question_templates')
            ->where('question_text', 'ระดับความปวดขณะนี้เท่าใด?')
            ->get(['id', 'response_rules'])
            ->filter(fn ($template) => json_decode($template->response_rules ?? 'null', true) === $expectedRules)
            ->pluck('id');

        DB::table('follow_up_question_templates')
            ->whereIn('id', $templateIds)
            ->update(['response_rules' => null, 'updated_at' => now()]);
    }
};
