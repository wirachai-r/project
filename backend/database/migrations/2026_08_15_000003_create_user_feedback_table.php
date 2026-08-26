<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_feedback', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 9);
            $table->string('feedback_type', 30);
            $table->string('target_type', 30)->nullable();
            $table->string('target_id', 50)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('category', 30)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->char('reviewed_by', 9)->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['status', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feedback');
    }
};
