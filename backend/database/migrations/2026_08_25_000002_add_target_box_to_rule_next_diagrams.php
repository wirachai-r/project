<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rule_next_diagrams', function (Blueprint $table) {
            $table->char('target_box_id', 10)->nullable()->after('diagram_id');
            $table->foreign('target_box_id')
                ->references('box_id')
                ->on('question_boxes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rule_next_diagrams', function (Blueprint $table) {
            $table->dropForeign(['target_box_id']);
            $table->dropColumn('target_box_id');
        });
    }
};
