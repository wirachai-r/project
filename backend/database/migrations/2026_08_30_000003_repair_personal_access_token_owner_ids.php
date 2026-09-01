<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select('user_id')
            ->orderBy('user_id')
            ->each(function (object $user): void {
                $userId = (string) $user->user_id;
                if (! ctype_digit($userId)) {
                    return;
                }

                $numericId = ltrim($userId, '0') ?: '0';

                DB::table('personal_access_tokens')
                    ->where('tokenable_type', 'App\\Models\\User')
                    ->where('tokenable_id', $numericId)
                    ->update(['tokenable_id' => $userId]);
            });
    }

    public function down(): void
    {
        // Restoring truncated owner IDs would invalidate working tokens.
    }
};
