<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adaptive_assessment_results', function (Blueprint $table) {
            $table->unsignedSmallInteger('supporting_symptom_count')->nullable()->after('match_percent');
            $table->unsignedSmallInteger('evaluated_symptom_count')->nullable()->after('supporting_symptom_count');
        });

        Schema::table('assessment_result_diseases', function (Blueprint $table) {
            $table->unsignedSmallInteger('supporting_symptom_count')->nullable()->after('match_percent');
            $table->unsignedSmallInteger('evaluated_symptom_count')->nullable()->after('supporting_symptom_count');
        });
    }

    public function down(): void
    {
        Schema::table('adaptive_assessment_results', function (Blueprint $table) {
            $table->dropColumn(['supporting_symptom_count', 'evaluated_symptom_count']);
        });

        Schema::table('assessment_result_diseases', function (Blueprint $table) {
            $table->dropColumn(['supporting_symptom_count', 'evaluated_symptom_count']);
        });
    }
};
