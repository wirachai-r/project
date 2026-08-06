<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->boolean('is_saved')->default(false)->after('assessment_status');
            $table->index(['user_id', 'is_saved', 'created_at']);
        });

        // รักษาประวัติเดิมไว้ ส่วนการประเมินใหม่จะรอผู้ใช้กดบันทึกเอง
        DB::table('assessments')
            ->where('assessment_status', 'C')
            ->update(['is_saved' => true]);
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_saved', 'created_at']);
            $table->dropColumn('is_saved');
        });
    }
};
