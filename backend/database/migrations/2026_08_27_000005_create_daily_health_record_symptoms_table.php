<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_health_record_symptoms', function (Blueprint $table) {
            $table->foreignId('daily_health_record_id')->constrained('daily_health_records')->cascadeOnDelete();
            $table->char('symptom_id', 10);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('symptom_id')->references('symptom_id')->on('main_symptoms')->cascadeOnDelete();
            $table->primary(['daily_health_record_id', 'symptom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_health_record_symptoms');
    }
};
