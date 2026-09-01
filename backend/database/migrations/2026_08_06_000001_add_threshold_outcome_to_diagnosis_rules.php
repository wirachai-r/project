<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

        $foreignExists = collect(Schema::getForeignKeys('diagnosis_rules'))
            ->contains(fn (array $foreign) => $foreign['columns'] === ['threshold_box_id']
                && $foreign['foreign_table'] === 'question_boxes');

        if (! $foreignExists) {
            Schema::table('diagnosis_rules', function (Blueprint $table) {
                $table->foreign('threshold_box_id', 'diag_rules_threshold_box_fk')
                    ->references('box_id')->on('question_boxes')->cascadeOnDelete();
            });
        }

        if (! Schema::hasIndex('diagnosis_rules', 'diag_rules_threshold_lookup_idx')) {
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
        $foreignExists = collect(Schema::getForeignKeys('diagnosis_rules'))
            ->contains(fn (array $foreign) => $foreign['columns'] === ['threshold_box_id']
                && $foreign['foreign_table'] === 'question_boxes');

        if ($foreignExists) {
            Schema::table('diagnosis_rules', function (Blueprint $table) {
                $table->dropForeign('diag_rules_threshold_box_fk');
            });
        }

        Schema::table('diagnosis_rules', function (Blueprint $table) {
            if (Schema::hasIndex('diagnosis_rules', 'diag_rules_threshold_lookup_idx')) {
                $table->dropIndex('diag_rules_threshold_lookup_idx');
            }
            $table->dropColumn(['threshold_outcome', 'threshold_box_id']);
        });
    }
};
