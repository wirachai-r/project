<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('body_area_group_symptoms', function (Blueprint $table) {
            $table->dropForeign(['symptom_id']);
        });

        Schema::table('body_area_group_symptoms', function (Blueprint $table) {
            $table->char('symptom_id', 10)->change();
            $table->foreign('symptom_id')
                ->references('symptom_id')
                ->on('main_symptoms')
                ->cascadeOnDelete();
        });

        Schema::table('body_area_groups', function (Blueprint $table) {
            $table->char('created_by', 9)->nullable()->change();
            $table->char('updated_by', 9)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('body_area_group_symptoms', function (Blueprint $table) {
            $table->dropForeign(['symptom_id']);
        });

        Schema::table('body_area_group_symptoms', function (Blueprint $table) {
            $table->char('symptom_id', 6)->change();
            $table->foreign('symptom_id')
                ->references('symptom_id')
                ->on('main_symptoms')
                ->cascadeOnDelete();
        });

        Schema::table('body_area_groups', function (Blueprint $table) {
            $table->char('created_by', 6)->nullable()->change();
            $table->char('updated_by', 6)->nullable()->change();
        });
    }
};
