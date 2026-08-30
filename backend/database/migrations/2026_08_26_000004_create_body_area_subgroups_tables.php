<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_area_subgroups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('body_area_group_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('name_en', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->char('status', 1)->default('1');
            $table->timestamps();

            $table->unique(['body_area_group_id', 'name']);
        });

        Schema::create('body_area_subgroup_symptoms', function (Blueprint $table) {
            $table->foreignId('body_area_subgroup_id')->constrained()->cascadeOnDelete();
            $table->char('symptom_id', 6);
            $table->foreign('symptom_id')->references('symptom_id')->on('main_symptoms')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->primary(['body_area_subgroup_id', 'symptom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_area_subgroup_symptoms');
        Schema::dropIfExists('body_area_subgroups');
    }
};
