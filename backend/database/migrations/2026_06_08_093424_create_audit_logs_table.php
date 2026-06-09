<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100);                  // เช่น assessment.create, user.login
            $table->string('model_type', 100)->nullable();  // เช่น App\Models\Assessment
            $table->string('model_id', 50)->nullable();     // id ของ record ที่ถูกกระทำ
            $table->json('old_values')->nullable();         // ค่าก่อนเปลี่ยน
            $table->json('new_values')->nullable();         // ค่าหลังเปลี่ยน
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            // FK
            $table->char('user_id', 9)->nullable();
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->nullOnDelete(); // null = system action

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('action');
            $table->index('model_type');
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
