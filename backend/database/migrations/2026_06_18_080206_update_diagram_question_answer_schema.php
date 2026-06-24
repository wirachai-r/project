<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * รวมทุก schema changes:
     * 1. ลบ symptom_id ออกจาก diagrams
     * 2. สร้าง symptom_diagrams (many-to-many)
     * 3. เพิ่ม question_image ใน question_boxes
     * 4. เพิ่ม choice_image + next_diagram_id ใน answer_choices
     */
    public function up(): void
    {
        // ============================================================
        // 1. ลบ symptom_id ออกจาก diagrams
        // ============================================================
        Schema::table('diagrams', function (Blueprint $table) {
            $table->dropForeign(['symptom_id']);
            $table->dropIndex(['symptom_id']);
            $table->dropColumn('symptom_id');
        });

        // ============================================================
        // 2. สร้างตาราง symptom_diagrams (many-to-many)
        //    1 symptom → หลาย diagram
        //    1 diagram → หลาย symptom (ค้นหาได้จากหลายอาการ)
        // ============================================================
        Schema::create('symptom_diagrams', function (Blueprint $table) {
            $table->char('symptom_id', 10);
            $table->foreign('symptom_id')
                  ->references('symptom_id')
                  ->on('main_symptoms')
                  ->cascadeOnDelete();

            $table->char('diagram_id', 5);
            $table->foreign('diagram_id')
                  ->references('diagram_id')
                  ->on('diagrams')
                  ->cascadeOnDelete();

            $table->primary(['symptom_id', 'diagram_id']);
        });

        // ============================================================
        // 3. เพิ่ม question_image ใน question_boxes
        //    รองรับการแสดงรูปประกอบคำถาม เช่น รูปร่างกาย, แผนภาพ
        // ============================================================
        Schema::table('question_boxes', function (Blueprint $table) {
            $table->string('question_image', 255)
                  ->nullable()
                  ->after('question_text_en');
            // เก็บเป็น path หรือ URL เช่น "images/questions/body-diagram.png"
        });

        // ============================================================
        // 4. เพิ่ม choice_image + next_diagram_id ใน answer_choices
        //    choice_image    → รูปประกอบตัวเลือก
        //    next_diagram_id → กระโดดข้าม diagram (เช่น กรอบ 15 ในตำรา)
        // ============================================================
        Schema::table('answer_choices', function (Blueprint $table) {
            // รูปประกอบตัวเลือก เช่น รูปอาการ รูปตำแหน่งปวด
            $table->string('choice_image', 255)
                  ->nullable()
                  ->after('choice_text_en');

            // กระโดดไป diagram อื่นเลย (ไม่ใช่แค่ box ถัดไป)
            // Logic: next_box_id มีค่า     → ถามกรอบถัดไปใน diagram เดิม
            //        next_diagram_id มีค่า  → กระโดดไป diagram อื่น
            //        ทั้งคู่เป็น null        → จบ flow → evaluate rules
            $table->char('next_diagram_id', 5)
                  ->nullable()
                  ->after('next_box_id');

            $table->foreign('next_diagram_id')
                  ->references('diagram_id')
                  ->on('diagrams')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // 4. ย้อน answer_choices
        Schema::table('answer_choices', function (Blueprint $table) {
            $table->dropForeign(['next_diagram_id']);
            $table->dropColumn(['choice_image', 'next_diagram_id']);
        });

        // 3. ย้อน question_boxes
        Schema::table('question_boxes', function (Blueprint $table) {
            $table->dropColumn('question_image');
        });

        // 2. ลบ symptom_diagrams
        Schema::dropIfExists('symptom_diagrams');

        // 1. คืน symptom_id กลับใน diagrams
        Schema::table('diagrams', function (Blueprint $table) {
            $table->char('symptom_id', 10)->nullable()->after('diagram_name_en');
            $table->foreign('symptom_id')
                  ->references('symptom_id')
                  ->on('main_symptoms')
                  ->restrictOnDelete();
            $table->index('symptom_id');
        });
    }
};
