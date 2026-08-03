<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. เพิ่ม google_id ( Unique และ Index ไว้ให้ค้นหาเร็ว )
            $table->string('google_id')->nullable()->unique()->after('email');

            // 2. เพิ่ม avatar เผื่อต้องการเก็บรูปโปรไฟล์จาก Google
            $table->string('avatar')->nullable()->after('sex');

            // 3. ปรับ password ให้เป็น null ได้ (สำหรับคนสมัครด้วย Google)
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
