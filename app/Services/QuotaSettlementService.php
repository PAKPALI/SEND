<?php

namespace App\Services;

use App\Models\QuotaPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuotaSettlementService
{
    public function queueForApproval(QuotaPayment $payment, array $verified, ?string $eventId = null, ?string $reference = null): bool
    {
        if (strtolower((string) ($verified['status'] ?? '')) !== 'success'
            || (string) ($verified['transaction_currency'] ?? '') !== $payment->currency
            || (int) ($verified['transaction_amount'] ?? -1) !== (int) $payment->amount) {
            throw new RuntimeException('PAYMENT_MISMATCH');
        }

        return DB::transaction(function () use ($payment, $eventId, $reference): bool {
            $locked = QuotaPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->status, ['paid', 'awaiting_approval'], true)) return false;
            $locked->update([
                'status' => 'awaiting_approval', 'paid_at' => now(), 'failure_reason' => null,
                'event_id' => $eventId ?: $locked->event_id,
                'kpp_reference' => $reference ?: $locked->kpp_reference,
            ]);
            return true;
        }, 3);
    }

    public function approve(QuotaPayment $payment, User $admin): bool
    {
        return DB::transaction(function () use ($payment, $admin): bool {
            $locked = QuotaPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') return false;
            if ($locked->status !== 'awaiting_approval') {
                throw new RuntimeException('Ce paiement n’est pas en attente de validation.');
            }

            $user = User::whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $user->increment('sms_credits', $locked->sms_quantity);
            $user->increment('whatsapp_credits', $locked->whatsapp_quantity);
            $locked->update([
                'status' => 'paid',
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'failure_reason' => null,
            ]);

            return true;
        }, 3);
    }

    public function reject(QuotaPayment $payment, User $admin, string $reason): bool
    {
        return DB::transaction(function () use ($payment, $admin, $reason): bool {
            $locked = QuotaPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'awaiting_approval') return false;
            $locked->update([
                'status' => 'rejected',
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'failure_reason' => mb_substr($reason ?: 'Paiement refusé par l’administrateur.', 0, 255),
            ]);
            return true;
        }, 3);
    }

    public function markFailed(QuotaPayment $payment, string $reason, ?string $eventId = null): void
    {
        DB::transaction(function () use ($payment, $reason, $eventId): void {
            $locked = QuotaPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->status, ['paid', 'awaiting_approval'], true)) return;
            $locked->update([
                'status' => 'failed', 'failure_reason' => mb_substr($reason ?: 'Paiement échoué', 0, 255),
                'failed_at' => now(), 'event_id' => $eventId ?: $locked->event_id,
            ]);
        }, 3);
    }
}
