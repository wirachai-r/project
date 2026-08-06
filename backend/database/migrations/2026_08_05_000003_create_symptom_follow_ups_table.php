<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('symptom_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->char('user_id', 9);
            $table->unsignedTinyInteger('severity');
            $table->decimal('temperature', 4, 1)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symptom_follow_ups');
    }
};
