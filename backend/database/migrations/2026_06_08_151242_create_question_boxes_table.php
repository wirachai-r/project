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
        Schema::create('question_boxes', function (Blueprint $table) {
            $table->char('box_id', 10)->primary();
            $table->string('question_text', 500);
            $table->string('question_text_en', 500)->nullable();
            $table->char('question_type', 1)->default('S'); // S=Single, M=Multiple
            $table->char('status', 1)->default('1'); // 1=Active, 2=Inactive

            // FK
            $table->char('diagram_id', 5);
            $table->foreign('diagram_id')
                ->references('diagram_id')
                ->on('diagrams')
                ->restrictOnDelete();

            // FK
            $table->char('created_by', 9)->nullable();
            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();

            // FK
            $table->char('updated_by', 9)->nullable();
            $table->foreign('updated_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('status');
            $table->index('diagram_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_boxes');
    }
};
