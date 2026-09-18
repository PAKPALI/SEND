<?php

namespace App\Services;

use App\Models\QuotaPayment;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KprimePayService
{
    public function createCheckout(QuotaPayment $payment, string $returnUrl): array
    {
        $response = $this->client()->withHeaders(['Idempotency-Key' => $payment->idempotency_key])->post($this->url('/checkout'), [
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'mode' => config('services.kprimepay.mode'),
            'with_fees' => config('services.kprimepay.with_fees'),
            'description' => "Achat de quotas : {$payment->sms_quantity} SMS et {$payment->whatsapp_quantity} WhatsApp",
            'return_url' => $returnUrl,
            'locale' => 'fr',
            'custom_meta_data' => ['quota_payment_id' => (string) $payment->id, 'user_id' => (string) $payment->user_id],
        ]);

        $payload = $this->validPayload($response, 'Impossible de créer le paiement KPrimePay.');
        $data = $payload['data'] ?? [];
        if (empty($data['checkout_url']) || empty($data['kpp_tx_reference'])) {
            throw new RuntimeException('KPrimePay a retourné une réponse incomplète.');
        }
        return $data;
    }

    public function paymentStatus(string $transactionId): array
    {
        $payload = $this->validPayload($this->client()->post($this->url('/transactions/debit-status'), ['transaction_id' => $transactionId]), 'Impossible de vérifier le paiement KPrimePay.');
        return $payload['data'] ?? [];
    }

    private function client()
    {
        $token = (string) config('services.kprimepay.token');
        if ($token === '') throw new RuntimeException('La clé KPrimePay n’est pas configurée.');
        $client = Http::acceptJson()->asJson()->withToken($token)->connectTimeout(5)->timeout(20);
        $caBundle = config('services.kprimepay.ca_bundle');
        if (is_string($caBundle) && $caBundle !== '' && is_file($caBundle)) $client = $client->withOptions(['verify' => $caBundle]);
        return $client;
    }

    private function url(string $path): string { return rtrim((string) config('services.kprimepay.base_url'), '/').$path; }

    private function validPayload(Response $response, string $fallback): array
    {
        $payload = $response->json();
        if (!$response->successful() || !is_array($payload) || ($payload['status'] ?? false) !== true) {
            throw new RuntimeException(is_array($payload) ? ($payload['message'] ?? $fallback) : $fallback);
        }
        return $payload;
    }
}
