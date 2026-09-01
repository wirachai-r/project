<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->char('type', 1)->default('S');
            $table->string('audience', 20)->default('all');
            $table->json('audience_filter')->nullable();
            $table->json('channels');
            $table->string('target_url', 2048)->nullable();
            $table->string('status', 30)->default('draft');
            $table->boolean('is_persistent')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->char('created_by', 9)->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['is_persistent', 'starts_at', 'expires_at'], 'notification_campaigns_persistent_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('id')->constrained('notification_campaigns')->nullOnDelete();
            $table->string('delivery_status', 20)->default('sent')->after('type');
            $table->text('delivery_error')->nullable()->after('delivery_status');
            $table->timestamp('delivered_at')->nullable()->after('delivery_error');
            $table->boolean('visible_in_app')->default(true)->after('delivered_at');
            $table->unique(['campaign_id', 'user_id']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 9)->unique();
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->json('disabled_types')->nullable();
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique(['campaign_id', 'user_id']);
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropColumn(['delivery_status', 'delivery_error', 'delivered_at', 'visible_in_app']);
        });
        Schema::dropIfExists('notification_campaigns');
    }
};
