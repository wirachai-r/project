<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['password_reset_otps', 'registration_otps'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'last_resent_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->timestamp('last_resent_at')->nullable()->after('expires_at');
                });
            }
        }
    }

    public function down(): void
    {
        // This is a repair migration. The column belongs to the OTP schema and
        // must not be removed when rolling this compatibility repair back.
    }
};
