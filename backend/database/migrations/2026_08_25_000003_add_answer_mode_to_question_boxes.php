<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_boxes', function (Blueprint $table) {
            $table->string('answer_mode', 20)->nullable()->after('question_type');
        });
    }

    public function down(): void
    {
        Schema::table('question_boxes', function (Blueprint $table) {
            $table->dropColumn('answer_mode');
        });
    }
};
