<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('assessment_type', 16)->default('classic')->index();
            $table->char('diagram_id', 5)->nullable()->change();
        });

        Schema::table('assessment_results', function (Blueprint $table) {
            $table->char('rule_id', 10)->nullable()->change();
        });

        Schema::table('adaptive_assessments', function (Blueprint $table) {
            $table->foreignId('assessment_id')->nullable()->unique()->constrained('assessments')->nullOnDelete();
        });

        // Preserve adaptive assessments completed before this integration was
        // introduced. Authenticated records are saved because those users had
        // no save-history action available on the old adaptive result screen.
        DB::table('adaptive_assessments')
            ->where('status', 'completed')
            ->whereNull('assessment_id')
            ->orderBy('id')
            ->chunkById(100, function ($adaptiveAssessments): void {
                foreach ($adaptiveAssessments as $adaptive) {
                    $assessmentId = DB::table('assessments')->insertGetId([
                        'user_id' => $adaptive->user_id,
                        'session_token' => $adaptive->session_token,
                        'symptom_id' => $adaptive->initial_symptom_id,
                        'diagram_id' => null,
                        'assessment_type' => 'adaptive',
                        'assessment_status' => 'C',
                        'started_at' => $adaptive->created_at,
                        'completed_at' => $adaptive->completed_at,
                        'is_saved' => $adaptive->user_id !== null,
                        'created_at' => $adaptive->created_at,
                        'updated_at' => now(),
                    ]);
                    $resultId = DB::table('assessment_results')->insertGetId([
                        'assessment_id' => $assessmentId,
                        'urgency_level' => 'W',
                        'should_see_doctor' => 'N',
                        'recommendation' => 'ผลคัดกรองจากระบบประเมินแบบคำถาม กรุณาพิจารณาร่วมกับอาการจริงและคำแนะนำจากบุคลากรทางการแพทย์',
                        'rule_id' => null,
                        'created_at' => $adaptive->completed_at,
                        'updated_at' => now(),
                    ]);

                    $results = DB::table('adaptive_assessment_results')
                        ->where('adaptive_assessment_id', $adaptive->id)
                        ->whereNotNull('disease_id')
                        ->orderBy('display_order')
                        ->get();
                    foreach ($results as $result) {
                        DB::table('assessment_result_diseases')->insert([
                            'assessment_result_id' => $resultId,
                            'disease_id' => $result->disease_id,
                            'display_order' => $result->display_order,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('adaptive_assessments')->where('id', $adaptive->id)->update([
                        'assessment_id' => $assessmentId,
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('assessments')->where('assessment_type', 'adaptive')->delete();
        Schema::table('adaptive_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_id');
        });
        Schema::table('assessment_results', function (Blueprint $table) {
            $table->char('rule_id', 10)->nullable(false)->change();
        });
        Schema::table('assessments', function (Blueprint $table) {
            $table->char('diagram_id', 5)->nullable(false)->change();
            $table->dropColumn('assessment_type');
        });
    }
};
