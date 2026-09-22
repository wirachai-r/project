<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adaptive_question_rules', function (Blueprint $table) {
            $table->text('evidence_source')->nullable();
            $table->string('evidence_status', 20)->default('unreviewed')->index();
            $table->char('reviewed_by', 9)->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->foreign('reviewed_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('adaptive_question_rules', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['evidence_source', 'evidence_status', 'reviewed_by', 'reviewed_at']);
        });
    }
};
