<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follow_up_question_templates', function (Blueprint $table) {
            $table->json('response_rules')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('follow_up_question_templates', function (Blueprint $table) {
            $table->dropColumn('response_rules');
        });
    }
};
