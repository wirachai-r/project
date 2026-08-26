<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_likes', function (Blueprint $table) {
            $table->id();
            $table->char('article_id', 10);
            $table->char('user_id', 9);
            $table->timestamps();

            $table->foreign('article_id')->references('article_id')->on('articles')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->unique(['article_id', 'user_id']);
        });

        Schema::create('article_comments', function (Blueprint $table) {
            $table->id();
            $table->char('article_id', 10);
            $table->char('user_id', 9);
            $table->text('content');
            $table->timestamps();

            $table->foreign('article_id')->references('article_id')->on('articles')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->index(['article_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_comments');
        Schema::dropIfExists('article_likes');
    }
};
