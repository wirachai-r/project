<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            $table->dropForeign(['disease_id']);
            $table->dropIndex(['disease_id']);
            $table->dropColumn('disease_id');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            $table->char('disease_id', 10)->nullable()->after('rule_id');
            $table->foreign('disease_id')
                ->references('disease_id')
                ->on('diseases')
                ->restrictOnDelete();
            $table->index('disease_id');
        });
    }
};
