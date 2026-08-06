<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('diseases', 'view_count')) {
            Schema::table('diseases', function (Blueprint $table) {
                $table->unsignedBigInteger('view_count')->default(0)->after('reference');
            });
        }
    }

    public function down(): void
    {
        // This migration repairs schema drift from an already-recorded migration.
        // Rolling it back must not remove a column that the original migration owns.
    }
};
