<?php

namespace App\Support;

use App\Models\AccountActivity;
use App\Models\User;
use Illuminate\Http\Request;

class AccountActivityLogger
{
    public static function record(User $user, string $event, Request $request, array $metadata = []): void
    {
        $currentToken = $user->currentAccessToken();
        AccountActivity::create([
            'user_id' => $user->user_id,
            'event' => $event,
            'device_name' => $request->string('device_name')->trim()->value() ?: data_get($currentToken, 'device_name'),
            'device_type' => $request->string('device_type')->trim()->value() ?: data_get($currentToken, 'device_type'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
