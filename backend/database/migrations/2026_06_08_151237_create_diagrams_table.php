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
        Schema::create('diagrams', function (Blueprint $table) {
            $table->char('diagram_id', 5)->primary();
            $table->string('diagram_name', 150);
            $table->string('diagram_name_en', 150)->nullable();
            $table->text('description')->nullable();
            $table->char('status', 1)->default('1'); // 1=Active, 2=Inactive

            // FK
            $table->char('symptom_id', 10);
            $table->foreign('symptom_id')
                ->references('symptom_id')
                ->on('main_symptoms')
                ->restrictOnDelete();

            // entry_box_id เพิ่มทีหลังใน add_entry_box_fk_to_diagrams
            // เพราะ question_boxes ยังไม่มีตอนนี้

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
            $table->index('symptom_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagrams');
    }
};
