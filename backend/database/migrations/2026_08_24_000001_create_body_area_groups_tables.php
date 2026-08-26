<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_area_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('name_en', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->char('status', 1)->default('1');
            $table->char('created_by', 6)->nullable();
            $table->char('updated_by', 6)->nullable();
            $table->timestamps();
        });

        Schema::create('body_area_group_symptoms', function (Blueprint $table) {
            $table->foreignId('body_area_group_id')->constrained()->cascadeOnDelete();
            $table->char('symptom_id', 6);
            $table->foreign('symptom_id')->references('symptom_id')->on('main_symptoms')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->primary(['body_area_group_id', 'symptom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_area_group_symptoms');
        Schema::dropIfExists('body_area_groups');
    }
};
