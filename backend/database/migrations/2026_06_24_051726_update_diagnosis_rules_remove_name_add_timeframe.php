<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosis_rules', function (Blueprint $table) {
            // ลบ columns ที่ไม่ต้องการ
            $table->dropColumn(['rule_name', 'rule_name_en', 'description']);

            // ลบ disease_id (ย้ายไป rule_diseases pivot)
            $table->dropForeign(['disease_id']);
            $table->dropColumn('disease_id');

            // เพิ่ม columns ใหม่
            $table->string('time_frame', 100)->nullable()->after('urgency_level');      // "ภายใน 24 ชั่วโมง"
            $table->string('time_frame_en', 100)->nullable()->after('time_frame');      // "Within 24 hours"
            $table->text('note')->nullable()->after('time_frame_en');                   // "ด่วน ให้น้ำเกลือระหว่างทาง"
            $table->text('note_en')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_rules', function (Blueprint $table) {
            $table->dropColumn(['time_frame', 'time_frame_en', 'note', 'note_en']);

            $table->string('rule_name', 150)->default('');
            $table->string('rule_name_en', 150)->nullable();
            $table->text('description')->nullable();

            $table->char('disease_id', 10)->nullable();
            $table->foreign('disease_id')
                ->references('disease_id')
                ->on('diseases')
                ->restrictOnDelete();
        });
    }
};
