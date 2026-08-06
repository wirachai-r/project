<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('symptom_categories', fn (Blueprint $table) =>
            $table->unique('category_name', 'uq_symptom_categories_name'));
        Schema::table('disease_categories', fn (Blueprint $table) =>
            $table->unique('category_name', 'uq_disease_categories_name'));
        Schema::table('article_categories', fn (Blueprint $table) =>
            $table->unique('category_name', 'uq_article_categories_name'));
        Schema::table('first_aid_categories', fn (Blueprint $table) =>
            $table->unique('category_name', 'uq_first_aid_categories_name'));
        Schema::table('main_symptoms', fn (Blueprint $table) =>
            $table->unique('symptom_name', 'uq_main_symptoms_name'));
        Schema::table('diseases', fn (Blueprint $table) =>
            $table->unique('disease_name', 'uq_diseases_name'));
        Schema::table('diagrams', fn (Blueprint $table) =>
            $table->unique('diagram_name', 'uq_diagrams_name'));
        Schema::table('articles', fn (Blueprint $table) =>
            $table->unique(['article_category_id', 'title'], 'uq_articles_category_title'));
        Schema::table('first_aids', fn (Blueprint $table) =>
            $table->unique(['first_aid_category_id', 'title'], 'uq_first_aids_category_title'));
        Schema::table('healthcare_facilities', fn (Blueprint $table) =>
            $table->unique(['facility_name', 'province', 'district'], 'uq_facilities_name_location'));
    }

    public function down(): void
    {
        Schema::table('healthcare_facilities', fn (Blueprint $table) =>
            $table->dropUnique('uq_facilities_name_location'));
        Schema::table('first_aids', fn (Blueprint $table) =>
            $table->dropUnique('uq_first_aids_category_title'));
        Schema::table('articles', fn (Blueprint $table) =>
            $table->dropUnique('uq_articles_category_title'));
        Schema::table('diagrams', fn (Blueprint $table) =>
            $table->dropUnique('uq_diagrams_name'));
        Schema::table('diseases', fn (Blueprint $table) =>
            $table->dropUnique('uq_diseases_name'));
        Schema::table('main_symptoms', fn (Blueprint $table) =>
            $table->dropUnique('uq_main_symptoms_name'));
        Schema::table('first_aid_categories', fn (Blueprint $table) =>
            $table->dropUnique('uq_first_aid_categories_name'));
        Schema::table('article_categories', fn (Blueprint $table) =>
            $table->dropUnique('uq_article_categories_name'));
        Schema::table('disease_categories', fn (Blueprint $table) =>
            $table->dropUnique('uq_disease_categories_name'));
        Schema::table('symptom_categories', fn (Blueprint $table) =>
            $table->dropUnique('uq_symptom_categories_name'));
    }
};
