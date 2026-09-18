<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KprimeMessagingService
{
    public function send(string $channel, string $phone, string $message, string $countryCode = 'TG', ?string $title = null): array
    {
        $config = config('services.kprimesms');
        $headers = [
            'Content-Type' => 'application/json',
            'token' => $config['token'],
            'key' => $config['key'],
        ];

        if (blank($config['base_url']) || blank($config['token']) || blank($config['key'])) {
            return ['status' => false, 'message' => 'KPrimeSMS n’est pas configuré.'];
        }

        try {
            $payload = $channel === 'sms'
                ? [
                    'sender' => $config['sender'],
                    'sender_id' => $config['sender_id'],
                    'country' => strtoupper($countryCode),
                    'phone_number' => $phone,
                    'message' => $message,
                    'response_url' => $config['response_url'],
                ]
                : [
                    'country' => strtoupper($countryCode),
                    'phone_number' => $phone,
                    'title' => $title ?: 'Message',
                    'content' => $message,
                    'response_url' => $config['response_url'],
                ];

            $endpoint = $channel === 'sms' ? '/sms/push' : '/whatsapp/template/text-message';
            $response = Http::connectTimeout(5)->timeout(20)->withHeaders($headers)->post(rtrim($config['base_url'], '/').$endpoint, $payload);
            $data = $response->json();
            $accepted = $response->successful() && is_array($data) && in_array($data['status'] ?? null, [true, 1, '1'], true);

            if (!$accepted) {
                Log::warning('KPrime message rejected', [
                    'channel' => $channel,
                    'http_status' => $response->status(),
                    'provider_status' => is_array($data) ? ($data['status'] ?? null) : null,
                ]);
            }

            return [
                'status' => $accepted,
                'message' => is_array($data) ? ($data['message'] ?? ($accepted ? 'Message accepté par KPrime.' : 'Message refusé par KPrime.')) : 'Réponse KPrime invalide.',
                'message_id' => is_array($data) ? ($data['message_id'] ?? $data['response_token'] ?? data_get($data, 'data.message_id')) : null,
                'raw' => is_array($data) ? $data : [],
            ];
        } catch (\Throwable $exception) {
            Log::warning('KPrime message request failed', ['channel' => $channel, 'error' => $exception->getMessage()]);
            return ['status' => false, 'message' => 'Le fournisseur est momentanément indisponible.'];
        }
    }
}
