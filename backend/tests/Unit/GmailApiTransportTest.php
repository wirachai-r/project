<?php

namespace Tests\Unit;

use App\Mail\Transport\GmailApiTransport;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class GmailApiTransportTest extends TestCase
{
    public function test_it_refreshes_the_access_token_and_sends_a_base64url_encoded_message(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
            'gmail.googleapis.com/*' => Http::response(['id' => 'gmail-message-id']),
        ]);

        $transport = new GmailApiTransport(
            app(HttpFactory::class),
            'client-id',
            'client-secret',
            'refresh-token',
        );
        $email = (new Email)
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('OTP')
            ->text('Your code is 123456');

        $sent = $transport->send(
            $email,
            new Envelope(new Address('sender@example.com'), [new Address('recipient@example.com')]),
        );

        $this->assertSame('gmail-message-id', $sent?->getMessageId());

        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'refresh-token');

        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send') {
                return false;
            }

            $raw = strtr((string) $request['raw'], '-_', '+/');
            $raw .= str_repeat('=', (4 - strlen($raw) % 4) % 4);
            $decoded = base64_decode($raw, true);

            return $request->hasHeader('Authorization', 'Bearer access-token')
                && is_string($decoded)
                && str_contains($decoded, 'Subject: OTP')
                && str_contains($decoded, 'Your code is 123456');
        });
    }
}
