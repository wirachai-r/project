<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_comment_reports', function (Blueprint $table) {
            $table->index('article_comment_id');
            $table->dropUnique(['article_comment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('article_comment_reports', function (Blueprint $table) {
            $table->unique(['article_comment_id', 'user_id']);
            $table->dropIndex(['article_comment_id']);
        });
    }
};
