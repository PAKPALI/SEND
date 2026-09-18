<?php

namespace App\Http\Controllers;

use App\Models\QuotaPayment;
use App\Services\KprimePayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class QuotaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $payments = $user->quotaPayments()->latest()->paginate($this->perPage($request, 10))->withQueryString();
        $prices = ['sms' => config('services.kprimepay.sms_unit_price', 25), 'whatsapp' => config('services.kprimepay.whatsapp_unit_price', 25)];
        $pendingPayments = $user->quotaPayments()->where('status', 'awaiting_approval')->latest()->get();
        return view('quota.index', compact('user', 'payments', 'prices', 'pendingPayments'));
    }

    public function checkout(Request $request, KprimePayService $kprimePay)
    {
        $data = $request->validate(['sms_quantity' => ['required', 'integer', 'min:0', 'max:100000'], 'whatsapp_quantity' => ['required', 'integer', 'min:0', 'max:100000'], 'terms_accepted' => ['accepted']]);
        if (($data['sms_quantity'] + $data['whatsapp_quantity']) < 1) return back()->withErrors(['sms_quantity' => 'Choisissez au moins un crédit SMS ou WhatsApp.'])->withInput();
        $amount = $data['sms_quantity'] * config('services.kprimepay.sms_unit_price', 25) + $data['whatsapp_quantity'] * config('services.kprimepay.whatsapp_unit_price', 25);
        $reference = 'KPF-'.strtoupper(Str::random(16));
        $payment = $request->user()->quotaPayments()->create([
            'transaction_id' => $reference, 'idempotency_key' => 'checkout-'.strtolower($reference),
            'sms_quantity' => $data['sms_quantity'], 'whatsapp_quantity' => $data['whatsapp_quantity'], 'amount' => $amount, 'currency' => 'XOF', 'status' => 'created',
        ]);
        try {
            $checkout = $kprimePay->createCheckout($payment, route('quota.return', ['transaction_id' => $reference]));
            $payment->update(['status' => 'pending', 'kpp_reference' => $checkout['kpp_tx_reference'], 'checkout_url' => $checkout['checkout_url'], 'expires_at' => $checkout['expires_at'] ?? now()->addDay()]);
        } catch (Throwable $exception) {
            $payment->update(['status' => 'failed', 'failure_reason' => mb_substr($exception->getMessage(), 0, 255), 'failed_at' => now()]);
            report($exception);
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
        return redirect()->away($payment->checkout_url);
    }

    public function returned() { return redirect()->route('quota.index')->with('info', 'Paiement reçu. Les crédits seront ajoutés après confirmation KPrimePay puis validation par l’administrateur.'); }
}
