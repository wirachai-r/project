<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_result_diseases', function (Blueprint $table) {
            $table->id();
            $table->integer('display_order')->default(0);

            // FK
            $table->unsignedBigInteger('assessment_result_id');
            $table->foreign('assessment_result_id')
                ->references('id')
                ->on('assessment_results')
                ->cascadeOnDelete();

            $table->char('disease_id', 10);
            $table->foreign('disease_id')
                ->references('disease_id')
                ->on('diseases')
                ->restrictOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index & Unique (ตั้งชื่อเองเพราะชื่อ auto-generate ยาวเกิน 64 ตัวอักษร)
            $table->unique(['assessment_result_id', 'disease_id'], 'ard_result_disease_unique');
            $table->index('assessment_result_id');
            $table->index('disease_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_result_diseases');
    }
};
