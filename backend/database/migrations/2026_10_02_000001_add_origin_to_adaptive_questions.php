<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GENERATED_PREFIXES = [
        'Generated candidate from internal disease-symptom co-occurrence and taxonomy',
        'สร้างอัตโนมัติจาก disease_symptoms ภายในระบบ',
        'Candidate จากโรคร่วม',
        'สร้างกลุ่มจาก disease_symptoms',
    ];

    public function up(): void
    {
        Schema::table('adaptive_questions', function (Blueprint $table): void {
            $table->string('origin', 16)->default('manual')->after('status')->index();
        });

        foreach (self::GENERATED_PREFIXES as $prefix) {
            DB::table('adaptive_questions')
                ->where('evidence_source', 'like', $prefix.'%')
                ->update(['origin' => 'generated']);
        }
    }

    public function down(): void
    {
        Schema::table('adaptive_questions', function (Blueprint $table): void {
            $table->dropIndex(['origin']);
            $table->dropColumn('origin');
        });
    }
};
