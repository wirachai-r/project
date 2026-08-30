<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_symptoms', function (Blueprint $table) {
            $table->char('disease_id', 10);
            $table->char('symptom_id', 10);
            $table->timestamps();

            $table->foreign('disease_id')
                ->references('disease_id')
                ->on('diseases')
                ->cascadeOnDelete();
            $table->foreign('symptom_id')
                ->references('symptom_id')
                ->on('main_symptoms')
                ->cascadeOnDelete();

            $table->primary(['disease_id', 'symptom_id']);
            $table->index('symptom_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_symptoms');
    }
};
