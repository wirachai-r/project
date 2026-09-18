<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adaptive_assessments', function (Blueprint $table) {
            $table->unsignedInteger('question_count')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('adaptive_assessments', function (Blueprint $table) {
            $table->unsignedTinyInteger('question_count')->default(0)->change();
        });
    }
};
