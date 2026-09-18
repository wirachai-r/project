<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_result_diseases', function (Blueprint $table) {
            $table->unsignedTinyInteger('match_percent')->nullable()->after('display_order');
        });

        DB::table('adaptive_assessment_results as adaptive_result')
            ->join('adaptive_assessments as adaptive', 'adaptive.id', '=', 'adaptive_result.adaptive_assessment_id')
            ->join('assessment_results as result', 'result.assessment_id', '=', 'adaptive.assessment_id')
            ->join('assessment_result_diseases as pivot', function ($join) {
                $join->on('pivot.assessment_result_id', '=', 'result.id')
                    ->on('pivot.disease_id', '=', 'adaptive_result.disease_id');
            })
            ->whereNotNull('adaptive.assessment_id')
            ->update(['pivot.match_percent' => DB::raw('adaptive_result.match_percent')]);
    }

    public function down(): void
    {
        Schema::table('assessment_result_diseases', function (Blueprint $table) {
            $table->dropColumn('match_percent');
        });
    }
};
