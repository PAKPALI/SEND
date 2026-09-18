<?php

namespace App\Http\Controllers;

use App\Models\QuotaPayment;
use App\Services\KprimePayService;
use App\Services\QuotaSettlementService;
use App\Services\QuotaNotificationService;
use Illuminate\Http\Request;
use Throwable;

class KprimePayWebhookController extends Controller
{
    public function __invoke(Request $request, KprimePayService $kprimePay, QuotaSettlementService $settlement, QuotaNotificationService $notifications)
    {
        $webhook = $this->normalize($request, $request->all());
        if (!$webhook) return response()->json(['status' => false, 'message' => 'INVALID_WEBHOOK'], 400);
        $payment = QuotaPayment::where('transaction_id', $webhook['transaction_id'])->first();
        if (!$payment) return response()->json(['status' => true, 'message' => 'IGNORED']);
        if ($payment->event_id === $webhook['event_id']) return response()->json(['status' => true, 'message' => 'DUPLICATE']);
        if ($webhook['event'] === 'collection.failed') {
            $settlement->markFailed($payment, $webhook['failure_reason'], $webhook['event_id']);
            return response()->json(['status' => true, 'message' => 'FAILED']);
        }
        if ($webhook['event'] !== 'collection.succeeded') return response()->json(['status' => true, 'message' => 'IGNORED']);
        if ($webhook['currency'] !== $payment->currency || $webhook['amount'] !== (int) $payment->amount) return response()->json(['status' => false, 'message' => 'PAYMENT_MISMATCH'], 422);
        try {
            $verified = $kprimePay->paymentStatus($payment->transaction_id);
            $queued = $settlement->queueForApproval($payment, $verified, $webhook['event_id'], $webhook['kpp_reference']);
            if ($queued) $notifications->paymentAwaitingApproval($payment->fresh());
        } catch (\RuntimeException $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['status' => false, 'message' => 'VERIFICATION_UNAVAILABLE'], 503);
        }
        return response()->json(['status' => true, 'message' => 'AWAITING_ADMIN_APPROVAL']);
    }

    private function normalize(Request $request, array $payload): ?array
    {
        $transactionId = (string) data_get($payload, 'data.transaction_id', '');
        if ($transactionId === '') return null;
        if (($payload['api_version'] ?? null) === '2.0') {
            $event = (string) ($payload['event'] ?? ''); $eventId = (string) ($payload['event_id'] ?? '');
            if ($request->header('X-API-BY') !== 'KPRIMESOFT' || $request->header('X-KPP-EVENT') !== $event || $request->header('X-KPP-EVENT-ID') !== $eventId || $eventId === '') return null;
            return ['event' => $event, 'event_id' => $eventId, 'transaction_id' => $transactionId, 'amount' => (int) data_get($payload, 'data.transaction_details.amount', -1), 'currency' => (string) data_get($payload, 'data.transaction_details.currency', ''), 'kpp_reference' => (string) data_get($payload, 'data.kpp_reference', ''), 'failure_reason' => (string) data_get($payload, 'data.failure_reason', 'Paiement échoué')];
        }
        if (($payload['object'] ?? null) !== 'payment' || ($payload['type'] ?? null) !== 'payment.web.checkout') return null;
        $status = strtolower((string) ($payload['status'] ?? '')); $paymentStatus = strtoupper((string) data_get($payload, 'data.payment_status', ''));
        $succeeded = $status === 'success' && $paymentStatus === 'TRANSACTION-COMPLETED';
        $failed = in_array($status, ['failed', 'failure', 'error'], true) || str_contains($paymentStatus, 'FAILED') || str_contains($paymentStatus, 'CANCEL');
        return ['event' => $succeeded ? 'collection.succeeded' : ($failed ? 'collection.failed' : 'collection.pending'), 'event_id' => 'v1_'.hash('sha256', json_encode($payload)), 'transaction_id' => $transactionId, 'amount' => (int) data_get($payload, 'data.transaction_amount', -1), 'currency' => (string) data_get($payload, 'data.transaction_currency', ''), 'kpp_reference' => (string) data_get($payload, 'data.kpp_tx_reference', ''), 'failure_reason' => (string) data_get($payload, 'data.failure_reason', 'Paiement échoué')];
    }
}
