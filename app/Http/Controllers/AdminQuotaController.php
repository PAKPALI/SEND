<?php

namespace App\Http\Controllers;

use App\Models\QuotaPayment;
use App\Services\QuotaNotificationService;
use App\Services\QuotaSettlementService;
use Illuminate\Http\Request;
use Throwable;

class AdminQuotaController extends Controller
{
    public function index(Request $request)
    {
        $query = QuotaPayment::with(['user', 'approver'])->whereIn('status', ['awaiting_approval', 'paid', 'rejected'])->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        $payments = $query->paginate($this->perPage($request, 20))->withQueryString();
        $prices = [
            'sms' => (int) config('services.kprimepay.sms_unit_price'),
            'sms_cost' => (int) config('services.kprimepay.sms_unit_cost'),
            'whatsapp' => (int) config('services.kprimepay.whatsapp_unit_price'),
            'whatsapp_cost' => (int) config('services.kprimepay.whatsapp_unit_cost'),
        ];
        $financials = $this->financials();
        return view('admin.quota.index', compact('payments', 'prices', 'financials'));
    }

    public function approve(Request $request, QuotaPayment $payment, QuotaSettlementService $settlement, QuotaNotificationService $notifications)
    {
        try {
            $approved = $settlement->approve($payment, $request->user());
        } catch (Throwable $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
        if (!$approved) return back()->with('info', 'Ce quota a déjà été traité.');
        $notifications->approved($payment->fresh());
        return back()->with('success', 'Quota validé et crédité sur le compte du client.');
    }

    public function reject(Request $request, QuotaPayment $payment, QuotaSettlementService $settlement, QuotaNotificationService $notifications)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $rejected = $settlement->reject($payment, $request->user(), $data['reason'] ?? '');
        if (!$rejected) return back()->with('info', 'Ce quota a déjà été traité.');
        $notifications->rejected($payment->fresh());
        return back()->with('success', 'Paiement refusé. Le client sera informé par email.');
    }

    private function financials(): array
    {
        $payments = QuotaPayment::whereIn('status', ['awaiting_approval', 'paid'])->get(['amount', 'sms_quantity', 'whatsapp_quantity']);
        $revenue = (int) $payments->sum('amount');
        $cost = (int) $payments->sum(fn ($payment) => ($payment->sms_quantity * config('services.kprimepay.sms_unit_cost')) + ($payment->whatsapp_quantity * config('services.kprimepay.whatsapp_unit_cost')));
        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $revenue - $cost,
            'pending' => QuotaPayment::where('status', 'awaiting_approval')->count(),
        ];
    }
}
