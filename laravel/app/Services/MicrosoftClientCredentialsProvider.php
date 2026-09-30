<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use PHPMailer\PHPMailer\OAuthTokenProvider;

/**
 * Client-credentials token provider for PHPMailer's XOAUTH2 (Office 365 SMTP).
 * Requires the Azure app to have SMTP.SendAsApp and an Exchange service principal
 * with access to the sender mailbox.
 */
class MicrosoftClientCredentialsProvider implements OAuthTokenProvider
{
    private ?string $token = null;

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $tenantId,
        private string $email
    ) {
    }

    private function fetchToken(): string
    {
        $response = Http::asForm()->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => 'https://outlook.office365.com/.default',
            'grant_type'    => 'client_credentials',
        ]);

        $token = $response->json('access_token');
        if (! $token) {
            throw new Exception('OAuth token error: ' . ($response->json('error_description') ?? $response->body()));
        }

        return $token;
    }

    public function getOauth64(): string
    {
        $this->token ??= $this->fetchToken();

        return base64_encode("user={$this->email}\001auth=Bearer {$this->token}\001\001");
    }
}
