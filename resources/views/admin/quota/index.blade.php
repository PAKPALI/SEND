@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">ADMINISTRATION</p>
        <h2>Validation des quotas.</h2>
        <p class="page-subtitle">Contrôlez les paiements avant de créditer les comptes clients.</p>
    </div>
</div>

<div class="admin-summary-grid">
    <div class="panel admin-summary-card"><span class="eyebrow">CHIFFRE D’AFFAIRES</span><strong>{{ number_format($financials['revenue'], 0, ',', ' ') }} F</strong><small>Paiements confirmés ou en attente</small></div>
    <div class="panel admin-summary-card"><span class="eyebrow">COÛT FOURNISSEUR</span><strong>{{ number_format($financials['cost'], 0, ',', ' ') }} F</strong><small>SMS {{ $prices['sms_cost'] }} F · WhatsApp {{ $prices['whatsapp_cost'] }} F</small></div>
    <div class="panel admin-summary-card profit"><span class="eyebrow">BÉNÉFICE ESTIMÉ</span><strong>{{ number_format($financials['profit'], 0, ',', ' ') }} F</strong><small>SMS vendu {{ $prices['sms'] }} F · marge {{ $prices['sms'] - $prices['sms_cost'] }} F</small></div>
    <div class="panel admin-summary-card pending"><span class="eyebrow">À VALIDER</span><strong>{{ $financials['pending'] }}</strong><small>Paiement(s) en attente d’approbation</small></div>
</div>

<section class="panel">
    <div class="section-head admin-list-head">
        <div><p class="eyebrow">PAIEMENTS KPRIMEPAY</p><h3>Demandes de crédit</h3></div>
        <form method="GET" class="admin-filter">
            <select name="status" onchange="this.form.submit()" aria-label="Filtrer les paiements">
                <option value="">Tous les statuts</option>
                <option value="awaiting_approval" @selected(request('status') === 'awaiting_approval')>À valider</option>
                <option value="paid" @selected(request('status') === 'paid')>Validés</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Refusés</option>
            </select>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>CLIENT</th><th>QUOTAS</th><th>MONTANT</th><th>BÉNÉFICE</th><th>ÉTAT</th><th>DATE</th><th></th></tr></thead>
            <tbody>
            @forelse($payments as $payment)
                @php
                    $cost = ($payment->sms_quantity * $prices['sms_cost']) + ($payment->whatsapp_quantity * $prices['whatsapp_cost']);
                    $profit = $payment->amount - $cost;
                @endphp
                <tr>
                    <td><div class="person-cell"><span class="avatar avatar-tiny">{{ strtoupper(substr($payment->user->name, 0, 1)) }}</span><div><strong>{{ $payment->user->name }}</strong><small>{{ $payment->user->email }}</small></div></div></td>
                    <td>{{ number_format($payment->sms_quantity, 0, ',', ' ') }} SMS · {{ number_format($payment->whatsapp_quantity, 0, ',', ' ') }} WhatsApp</td>
                    <td><strong>{{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</strong></td>
                    <td class="{{ $profit >= 0 ? 'profit-text' : 'danger-text' }}">{{ number_format($profit, 0, ',', ' ') }} F</td>
                    <td><span class="status-pill {{ $payment->status }}">{{ ['awaiting_approval'=>'À valider','paid'=>'Validé','rejected'=>'Refusé'][$payment->status] ?? $payment->status }}</span></td>
                    <td>{{ $payment->paid_at?->format('d/m/Y · H:i') ?? $payment->created_at->format('d/m/Y · H:i') }}</td>
                    <td>
                        @if($payment->status === 'awaiting_approval')
                            <div class="approval-actions">
                                <form method="POST" action="{{ route('admin.quota.approve', $payment) }}">@csrf<button class="table-action profit-text" type="submit">✓ Valider</button></form>
                                <form method="POST" action="{{ route('admin.quota.reject', $payment) }}">@csrf<input class="approval-reason" name="reason" placeholder="Motif facultatif"><button class="table-action danger-text" type="submit">Refuser</button></form>
                            </div>
                        @else
                            <small class="muted">{{ $payment->approver?->name ?: '—' }}</small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><span>◈</span><p>Aucun paiement à afficher.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('components.pagination', ['paginator' => $payments])
</section>
@endsection
