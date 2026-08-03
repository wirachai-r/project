<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_boxes', function (Blueprint $table) {
            $table->unsignedTinyInteger('min_required')->nullable()->after('question_type');

            $table->char('yes_next_box_id', 10)->nullable()->after('min_required');
            $table->char('yes_next_diagram_id', 5)->nullable()->after('yes_next_box_id');
            $table->char('no_next_box_id', 10)->nullable()->after('yes_next_diagram_id');
            $table->char('no_next_diagram_id', 5)->nullable()->after('no_next_box_id');

            // ➕ เพิ่มคอลัมน์รายละเอียด (สามารถเปลี่ยนชื่อคอลัมน์ตามต้องการได้เลย เช่น detail, description)
            $table->text('detail')->nullable()->after('no_next_diagram_id');
        });

        // FK แยกออกมาต่างหาก เพราะ yes_next_box_id/no_next_box_id อ้างถึงตารางตัวเอง (self-reference)
        Schema::table('question_boxes', function (Blueprint $table) {
            $table->foreign('yes_next_box_id')
                ->references('box_id')->on('question_boxes')->nullOnDelete();
            $table->foreign('yes_next_diagram_id')
                ->references('diagram_id')->on('diagrams')->nullOnDelete();
            $table->foreign('no_next_box_id')
                ->references('box_id')->on('question_boxes')->nullOnDelete();
            $table->foreign('no_next_diagram_id')
                ->references('diagram_id')->on('diagrams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('question_boxes', function (Blueprint $table) {
            $table->dropForeign(['yes_next_box_id']);
            $table->dropForeign(['yes_next_diagram_id']);
            $table->dropForeign(['no_next_box_id']);
            $table->dropForeign(['no_next_diagram_id']);
            $table->dropColumn([
                'min_required',
                'yes_next_box_id', 'yes_next_diagram_id',
                'no_next_box_id', 'no_next_diagram_id',
                'detail', // ➕ อย่าลืมสั่งลบคอลัมน์ที่เพิ่มเข้ามาในฟังก์ชัน down ด้วย
            ]);
        });
    }
};
