<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('articles', 'view_count')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->unsignedBigInteger('view_count')->default(0)->after('published_at');
            });

            if (Schema::hasTable('article_views')) {
                DB::table('article_views')
                    ->select('article_id', DB::raw('COUNT(*) as total'))
                    ->groupBy('article_id')
                    ->orderBy('article_id')
                    ->chunk(200, function ($rows) {
                        foreach ($rows as $row) {
                            DB::table('articles')
                                ->where('article_id', $row->article_id)
                                ->update(['view_count' => $row->total]);
                        }
                    });
            }
        }

        if (! Schema::hasColumn('first_aids', 'view_count')) {
            Schema::table('first_aids', function (Blueprint $table) {
                $table->unsignedBigInteger('view_count')->default(0)->after('published_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('articles', 'view_count')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('view_count');
            });
        }

        if (Schema::hasColumn('first_aids', 'view_count')) {
            Schema::table('first_aids', function (Blueprint $table) {
                $table->dropColumn('view_count');
            });
        }
    }
};
