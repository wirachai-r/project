<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('diagnosis_rules', 'threshold_outcome')) {
            Schema::table('diagnosis_rules', function (Blueprint $table) {
                $table->string('threshold_outcome', 3)->nullable()->after('diagram_id');
            });
        }

        if (! Schema::hasColumn('diagnosis_rules', 'threshold_box_id')) {
            Schema::table('diagnosis_rules', function (Blueprint $table) {
                $table->string('threshold_box_id', 10)->nullable()->after('threshold_outcome');
            });
        }

        $foreignExists = count(DB::select(<<<'SQL'
            SELECT 1
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'diagnosis_rules'
              AND COLUMN_NAME = 'threshold_box_id'
              AND REFERENCED_TABLE_NAME = 'question_boxes'
        SQL)) > 0;

        if (! $foreignExists) {
            Schema::table('diagnosis_rules', function (Blueprint $table) {
                $table->foreign('threshold_box_id', 'diag_rules_threshold_box_fk')
                    ->references('box_id')->on('question_boxes')->cascadeOnDelete();
            });
        }

        $indexExists = count(DB::select(<<<'SQL'
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'diagnosis_rules'
              AND INDEX_NAME = 'diag_rules_threshold_lookup_idx'
        SQL)) > 0;

        if (! $indexExists) {
            Schema::table('diagnosis_rules', function (Blueprint $table) {
                $table->index(
                    ['diagram_id', 'threshold_box_id', 'threshold_outcome'],
                    'diag_rules_threshold_lookup_idx'
                );
            });
        }
    }

    public function down(): void
    {
        $foreign = DB::selectOne(<<<'SQL'
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'diagnosis_rules'
              AND COLUMN_NAME = 'threshold_box_id'
              AND REFERENCED_TABLE_NAME = 'question_boxes'
        SQL);

        if ($foreign) {
            $name = str_replace('`', '``', $foreign->CONSTRAINT_NAME);
            DB::statement("ALTER TABLE `diagnosis_rules` DROP FOREIGN KEY `{$name}`");
        }

        Schema::table('diagnosis_rules', function (Blueprint $table) {
            $table->dropIndex('diag_rules_threshold_lookup_idx');
            $table->dropColumn(['threshold_outcome', 'threshold_box_id']);
        });
    }
};
