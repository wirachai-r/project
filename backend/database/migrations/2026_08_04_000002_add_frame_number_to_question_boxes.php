<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_boxes', function (Blueprint $table) {
            $table->string('frame_number', 20)->nullable()->after('box_id');
            $table->index(['diagram_id', 'frame_number']);
        });
    }

    public function down(): void
    {
        Schema::table('question_boxes', function (Blueprint $table) {
            $table->dropIndex(['diagram_id', 'frame_number']);
            $table->dropColumn('frame_number');
        });
    }
};
