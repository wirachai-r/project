<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('user_id')->constrained('article_comments')->cascadeOnDelete();
            $table->timestamp('hidden_at')->nullable()->after('content');
        });

        Schema::create('article_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_comment_id')->constrained('article_comments')->cascadeOnDelete();
            $table->char('user_id', 9);
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->unique(['article_comment_id', 'user_id']);
        });

        Schema::create('article_comment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_comment_id')->constrained('article_comments')->cascadeOnDelete();
            $table->char('user_id', 9);
            $table->string('reason', 50);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('pending');
            $table->char('reviewed_by', 9)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('user_id')->on('users')->nullOnDelete();
            $table->unique(['article_comment_id', 'user_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_comment_reports');
        Schema::dropIfExists('article_comment_likes');
        Schema::table('article_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('hidden_at');
        });
    }
};
