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
        Schema::create('article_views', function (Blueprint $table) {
            $table->id();
            $table->integer('read_duration')->default(0); // วินาทีที่อ่าน
            $table->char('is_completed', 1)->default('N'); // Y=Yes, N=No

            // FK
            $table->char('user_id', 9)->nullable();
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->char('article_id', 10);
            $table->foreign('article_id')
                ->references('article_id')
                ->on('articles')
                ->cascadeOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('user_id');
            $table->index('article_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_views');
    }
};
