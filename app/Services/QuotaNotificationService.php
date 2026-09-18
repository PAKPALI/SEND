<?php

namespace App\Services;

use App\Models\QuotaPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class QuotaNotificationService
{
    public function paymentAwaitingApproval(QuotaPayment $payment): void
    {
        $payment->loadMissing('user');
        $this->send(
            (string) config('services.send.admin_email'),
            'SEND · Paiement de quotas à valider',
            $this->paymentDetails($payment, 'Un paiement de quotas a été confirmé par KPrimePay et attend votre validation.')
        );
    }

    public function approved(QuotaPayment $payment): void
    {
        $payment->loadMissing('user');
        $this->send(
            $payment->user->email,
            'SEND · Vos quotas sont disponibles',
            $this->paymentDetails($payment, 'Votre paiement a été validé par l’administrateur. Vos quotas sont maintenant disponibles dans SEND.')
        );
    }

    public function rejected(QuotaPayment $payment): void
    {
        $payment->loadMissing('user');
        $this->send(
            $payment->user->email,
            'SEND · Votre paiement de quotas a été refusé',
            $this->paymentDetails($payment, 'Votre paiement de quotas a été refusé. Consultez votre espace SEND pour plus de détails.')
        );
    }

    private function send(string $recipient, string $subject, string $body): void
    {
        if ($recipient === '') return;
        try {
            Mail::raw($body, function ($message) use ($recipient, $subject): void {
                $message->to($recipient)->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::error('Quota email notification failed', ['recipient' => $recipient, 'error' => $exception->getMessage()]);
        }
    }

    private function paymentDetails(QuotaPayment $payment, string $intro): string
    {
        $smsPrice = (int) config('services.kprimepay.sms_unit_price');
        $smsCost = (int) config('services.kprimepay.sms_unit_cost');
        $whatsappPrice = (int) config('services.kprimepay.whatsapp_unit_price');
        $whatsappCost = (int) config('services.kprimepay.whatsapp_unit_cost');
        $revenue = (int) $payment->amount;
        $cost = ($payment->sms_quantity * $smsCost) + ($payment->whatsapp_quantity * $whatsappCost);
        $profit = $revenue - $cost;

        return implode(PHP_EOL, [
            $intro,
            '',
            'Client : '.$payment->user->name.' <'.$payment->user->email.'>',
            'Transaction : '.$payment->transaction_id,
            'Référence KPrimePay : '.($payment->kpp_reference ?: '—'),
            'Statut : '.$payment->status,
            'SMS : '.$payment->sms_quantity.' × '.$smsPrice.' F = '.($payment->sms_quantity * $smsPrice).' F (coût estimé : '.($payment->sms_quantity * $smsCost).' F)',
            'WhatsApp : '.$payment->whatsapp_quantity.' × '.$whatsappPrice.' F = '.($payment->whatsapp_quantity * $whatsappPrice).' F (coût estimé : '.($payment->whatsapp_quantity * $whatsappCost).' F)',
            'Montant payé : '.$revenue.' '.$payment->currency,
            'Coût estimé : '.$cost.' '.$payment->currency,
            'Bénéfice estimé : '.$profit.' '.$payment->currency,
            '',
            'SEND',
        ]);
    }
}
