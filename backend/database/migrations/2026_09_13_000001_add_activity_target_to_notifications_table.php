
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('target_type', 40)->nullable()->after('body');
            $table->string('target_id', 64)->nullable()->after('target_type');
            $table->date('target_date')->nullable()->after('target_id');
            $table->index(['user_id', 'target_type', 'target_id'], 'notifications_activity_target_idx');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_activity_target_idx');
            $table->dropColumn(['target_type', 'target_id', 'target_date']);
        });
    }
};
