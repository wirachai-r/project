<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'device_token' => ['required', 'string', 'max:512'],
            'device_type' => ['required', 'in:android,ios'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $device = UserDevice::query()->updateOrCreate(
            ['device_token' => $data['device_token']],
            [
                'user_id' => $request->user()->user_id,
                'device_type' => $data['device_type'],
                'device_name' => $data['device_name'] ?? null,
                'status' => '1',
                'last_active_at' => now(),
            ],
        );

        return response()->json(['data' => ['id' => $device->id]], 201);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['device_token' => ['required', 'string', 'max:512']]);
        UserDevice::query()
            ->where('user_id', $request->user()->user_id)
            ->where('device_token', $data['device_token'])
            ->delete();

        return response()->noContent();
    }
}
