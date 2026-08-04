<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_next_diagrams', function (Blueprint $table) {
            $table->id();
            $table->char('rule_id', 10);
            $table->char('diagram_id', 5);
            $table->integer('display_order')->default(0);
            $table->string('prompt_text', 255)->nullable();
            $table->timestamps();

            $table->foreign('rule_id')
                ->references('rule_id')
                ->on('diagnosis_rules')
                ->cascadeOnDelete();
            $table->foreign('diagram_id')
                ->references('diagram_id')
                ->on('diagrams')
                ->restrictOnDelete();

            $table->unique(['rule_id', 'diagram_id']);
            $table->index('rule_id');
            $table->index('diagram_id');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->foreignId('parent_assessment_id')
                ->nullable()
                ->after('id')
                ->constrained('assessments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_assessment_id');
        });

        Schema::dropIfExists('rule_next_diagrams');
    }
};
