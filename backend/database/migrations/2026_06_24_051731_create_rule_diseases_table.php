<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_diseases', function (Blueprint $table) {
            $table->id();
            $table->integer('display_order')->default(0); // ลำดับการแสดงผล

            // FK
            $table->char('rule_id', 10);
            $table->foreign('rule_id')
                ->references('rule_id')
                ->on('diagnosis_rules')
                ->cascadeOnDelete();

            $table->char('disease_id', 10);
            $table->foreign('disease_id')
                ->references('disease_id')
                ->on('diseases')
                ->restrictOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index & Unique
            $table->unique(['rule_id', 'disease_id']);
            $table->index('rule_id');
            $table->index('disease_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_diseases');
    }
};
