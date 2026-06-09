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
        Schema::create('answer_choices', function (Blueprint $table) {
            $table->char('choice_id', 10)->primary();
            $table->string('choice_text', 255);
            $table->string('choice_text_en', 255)->nullable();
            $table->integer('order')->default(0);
            $table->char('status', 1)->default('1'); // 1=Active, 2=Inactive

            // FK
            $table->char('box_id', 10);
            $table->foreign('box_id')
                ->references('box_id')
                ->on('question_boxes')
                ->restrictOnDelete();

            // ชี้ไปยัง question_box ถัดไป (nullable = จบ flow)
            $table->char('next_box_id', 10)->nullable();
            $table->foreign('next_box_id')
                ->references('box_id')
                ->on('question_boxes')
                ->nullOnDelete();

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
            $table->index('box_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answer_choices');
    }
};
