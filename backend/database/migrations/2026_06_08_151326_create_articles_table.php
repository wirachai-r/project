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
        Schema::create('articles', function (Blueprint $table) {
            $table->char('article_id', 10)->primary();
            $table->string('title', 255);
            $table->string('title_en', 255)->nullable();
            $table->text('content');
            $table->text('content_en')->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->char('status', 1)->default('D'); // P=Published, D=Draft, A=Archived
            $table->timestamp('published_at')->nullable();

            // FK
            $table->char('article_category_id', 6);
            $table->foreign('article_category_id')
                ->references('article_category_id')
                ->on('article_categories')
                ->restrictOnDelete();

            // FK
            $table->char('created_by', 9)->nullable();
            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();

            // FK
            $table->char('updated_by', 9)->nullable();
            $table->foreign('updated_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('status');
            $table->index('article_category_id');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
