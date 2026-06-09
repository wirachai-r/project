<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assessment_answers', function (Blueprint $table) {
            $table->unique(['assessment_id', 'box_id', 'choice_id'], 'uq_assessment_box_choice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_answers', function (Blueprint $table) {
            $table->dropUnique('uq_assessment_box_choice');
        });
    }
};
