<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::connection()->getDriverName() === 'sqlite'
            && Schema::hasColumn('diagnosis_rules', 'disease_id')
            && Schema::hasIndex('diagnosis_rules', 'diagnosis_rules_disease_id_index')
        ) {
            Schema::table('diagnosis_rules', function (Blueprint $table) {
                $table->dropIndex('diagnosis_rules_disease_id_index');
            });
        }
    }

    public function down(): void
    {
        if (
            DB::connection()->getDriverName() === 'sqlite'
            && Schema::hasColumn('diagnosis_rules', 'disease_id')
            && ! Schema::hasIndex('diagnosis_rules', 'diagnosis_rules_disease_id_index')
        ) {
            Schema::table('diagnosis_rules', function (Blueprint $table) {
                $table->index('disease_id');
            });
        }
    }
};
