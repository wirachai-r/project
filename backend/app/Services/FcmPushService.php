<?php

namespace App\Services;

use App\Models\NotificationCampaign;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmPushService
{
    private ?string $accessToken = null;

    public function sendToUser(User $user, NotificationCampaign $campaign): bool
    {
        $devices = UserDevice::query()
            ->where('user_id', $user->user_id)
            ->where('status', '1')
            ->get();

        if ($devices->isEmpty()) {
            return false;
        }

        $sent = false;
        foreach ($devices as $device) {
            try {
                $response = Http::withToken($this->accessToken())
                    ->post($this->endpoint(), [
                        'message' => [
                            'token' => $device->device_token,
                            'notification' => [
                                'title' => $campaign->title,
                                'body' => trim(html_entity_decode(strip_tags($campaign->body))),
                            ],
                            'data' => array_filter([
                                'campaign_id' => (string) $campaign->id,
                                'payload' => $campaign->target_url,
                            ]),
                            'android' => ['priority' => 'high'],
                        ],
                    ]);

                if ($response->successful()) {
                    $sent = true;

                    continue;
                }

                if (in_array($response->status(), [400, 404], true)) {
                    $device->update(['status' => '2']);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }

    private function endpoint(): string
    {
        $projectId = (string) config('services.firebase.project_id');
        if ($projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        return "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    private function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $path = (string) config('services.firebase.credentials');
        $path = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)
            ? $path
            : base_path($path);
        $credentials = json_decode((string) @file_get_contents($path), true);
        if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Firebase service-account credentials are not configured.');
        }

        $now = time();
        $encode = static fn (array $value): string => rtrim(strtr(base64_encode(json_encode($value)), '+/', '-_'), '=');
        $header = $encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claims = $encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);
        $unsigned = "{$header}.{$claims}";
        openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ])->throw();

        return $this->accessToken = (string) $response->json('access_token');
    }
}
