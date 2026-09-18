<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\Factory as HttpFactory;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SEND_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $refreshToken,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        try {
            $tokenResponse = $this->http->asForm()->post(self::TOKEN_URL, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ])->throw();

            $accessToken = $tokenResponse->json('access_token');
            if (! is_string($accessToken) || $accessToken === '') {
                throw new TransportException('Google OAuth token response did not contain an access token.');
            }

            $sendResponse = $this->http
                ->withToken($accessToken)
                ->acceptJson()
                ->post(self::SEND_URL, [
                    'raw' => $this->base64UrlEncode($message->toString()),
                ])
                ->throw();

            $gmailMessageId = $sendResponse->json('id');
            if (is_string($gmailMessageId) && $gmailMessageId !== '') {
                $message->setMessageId($gmailMessageId);
            }
        } catch (TransportException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new TransportException(
                'Unable to send email through the Gmail API: '.$exception->getMessage(),
                previous: $exception,
            );
        }
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
