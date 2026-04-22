<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PayPalService
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $clientId,
        private readonly string $secret,
        private readonly string $baseUrl,
    ) {}

    public function getClientId(): string
    {
        return $this->clientId;
    }

    private function getAccessToken(): string
    {
        $response = $this->http->request('POST', $this->baseUrl . '/v1/oauth2/token', [
            'auth_basic' => [$this->clientId, $this->secret],
            'headers'    => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'       => 'grant_type=client_credentials',
        ]);

        return $response->toArray()['access_token'];
    }

    public function createOrder(float $amount): string
    {
        $token = $this->getAccessToken();

        $response = $this->http->request('POST', $this->baseUrl . '/v2/checkout/orders', [
            'auth_bearer' => $token,
            'json'        => [
                'intent'         => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value'         => number_format($amount, 2, '.', ''),
                    ],
                ]],
            ],
        ]);

        return $response->toArray()['id'];
    }

    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = $this->http->request(
            'POST',
            $this->baseUrl . '/v2/checkout/orders/' . $orderId . '/capture',
            [
                'auth_bearer' => $token,
                'headers'     => ['Content-Type' => 'application/json'],
                'body'        => '{}',
            ]
        );

        return $response->toArray();
    }
}
