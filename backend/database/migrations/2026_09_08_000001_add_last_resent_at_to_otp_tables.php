<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->timestamp('last_resent_at')->nullable()->after('expires_at');
        });

        Schema::table('registration_otps', function (Blueprint $table) {
            $table->timestamp('last_resent_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->dropColumn('last_resent_at');
        });

        Schema::table('registration_otps', function (Blueprint $table) {
            $table->dropColumn('last_resent_at');
        });
    }
};
