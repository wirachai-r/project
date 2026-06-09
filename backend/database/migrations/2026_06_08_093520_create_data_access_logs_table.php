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
        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('data_type', 100);           // เช่น assessment, user_profile
            $table->char('data_owner_id', 9);           // user_id เจ้าของข้อมูล
            $table->string('action', 20);               // read, export, print
            $table->string('purpose', 255)->nullable(); // วัตถุประสงค์การเข้าถึง
            $table->string('ip_address', 45)->nullable();

            // FK
            $table->char('accessed_by', 9);
            $table->foreign('accessed_by')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('data_type');
            $table->index('data_owner_id');
            $table->index('accessed_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_access_logs');
    }
};
