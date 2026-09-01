<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_clarification_sessions', function (Blueprint $table) {
            $table->index('assessment_id', 'ai_clarification_session_assessment');
        });
        Schema::table('ai_clarification_sessions', function (Blueprint $table) {
            $table->dropUnique(['assessment_id', 'box_id']);
            $table->index(['assessment_id', 'box_id', 'status'], 'ai_clarification_session_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('ai_clarification_sessions', function (Blueprint $table) {
            $table->unique(['assessment_id', 'box_id']);
            $table->dropIndex('ai_clarification_session_lookup');
            $table->dropIndex('ai_clarification_session_assessment');
        });
    }
};
