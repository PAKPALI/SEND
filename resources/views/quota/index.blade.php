@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">ALIMENTATION DU COMPTE</p>
        <h2>Crédits & paiements.</h2>
        <p class="page-subtitle">Gardez toujours une longueur d’avance sur vos campagnes.</p>
    </div>
</div>

@if($pendingPayments->isNotEmpty())
    <div class="callout quota-pending-callout"><span class="callout-icon">◷</span><div><strong>Quota en attente d’approbation.</strong><p>Votre paiement a bien été confirmé par KPrimePay. L’administrateur doit valider le crédit avant que vous puissiez envoyer vos messages.</p></div></div>
@endif

<div class="credit-hero">
    <div><span class="hero-chip"><span class="pulse"></span> SOLDE DISPONIBLE</span><h2>{{ number_format($user->sms_credits + $user->whatsapp_credits, 0, ',', ' ') }} <small>crédits</small></h2><p>Les crédits sont ajoutés après confirmation KPrimePay et validation administrative.</p></div>
    <div class="credit-split"><div><span class="dot sms"></span><strong>{{ number_format($user->sms_credits, 0, ',', ' ') }}</strong><small>SMS</small></div><div><span class="dot wa"></span><strong>{{ number_format($user->whatsapp_credits, 0, ',', ' ') }}</strong><small>WhatsApp</small></div></div>
</div>

<div class="content-grid two-thirds">
    <section class="panel">
        <div class="section-head"><div><p class="eyebrow">ACHETER DES CRÉDITS</p><h3>Choisissez votre volume</h3></div><span class="secure-badge">⌁ KPrimePay sécurisé</span></div>
        <form method="POST" action="{{ route('quota.checkout') }}" class="quota-form">
            @csrf
            <div class="quota-input-card"><div class="quota-icon sms">✉</div><div><strong>Crédits SMS</strong><small>{{ number_format($prices['sms'], 0, ',', ' ') }} FCFA / message · bénéfice administrateur estimé : {{ number_format($prices['sms'] - config('services.kprimepay.sms_unit_cost'), 0, ',', ' ') }} F</small></div><div class="quantity-control"><button type="button" data-stepper="down" data-target="sms_quantity">−</button><input type="number" name="sms_quantity" id="sms_quantity" value="100" min="0" max="100000" data-price="{{ $prices['sms'] }}"><button type="button" data-stepper="up" data-target="sms_quantity">＋</button></div></div>
            <div class="quota-input-card"><div class="quota-icon whatsapp">◉</div><div><strong>Crédits WhatsApp</strong><small>{{ number_format($prices['whatsapp'], 0, ',', ' ') }} FCFA / message</small></div><div class="quantity-control"><button type="button" data-stepper="down" data-target="whatsapp_quantity">−</button><input type="number" name="whatsapp_quantity" id="whatsapp_quantity" value="100" min="0" max="100000" data-price="{{ $prices['whatsapp'] }}"><button type="button" data-stepper="up" data-target="whatsapp_quantity">＋</button></div></div>
            <div class="total-line"><span>Total à payer</span><strong><span data-total>{{ number_format(($prices['sms'] + $prices['whatsapp']) * 100, 0, ',', ' ') }}</span> FCFA</strong></div>
            <label class="check-row terms"><input type="checkbox" name="terms_accepted" required><span>J’accepte les conditions de paiement et la redirection vers KPrimePay.</span></label>
            <button class="primary-button wide" type="submit">Continuer vers KPrimePay <span>→</span></button>
        </form>
    </section>
    <aside class="panel pay-info"><div class="pay-orb">◈</div><h3>Simple, transparent,<br><span>sans surprise.</span></h3><p>Après le paiement, l’administrateur reçoit le détail de la transaction et valide manuellement vos quotas.</p><div class="pay-features"><div><span>✓</span><p><strong>Confirmation KPrimePay</strong><small>Votre paiement est vérifié côté serveur</small></p></div><div><span>✓</span><p><strong>Validation administrative</strong><small>Les crédits sont ajoutés après approbation</small></p></div><div><span>✓</span><p><strong>Prix clairs</strong><small>SMS {{ $prices['sms'] }} FCFA · WhatsApp {{ $prices['whatsapp'] }} FCFA</small></p></div></div></aside>
</div>

<section class="panel activity-panel">
    <div class="section-head"><div><p class="eyebrow">HISTORIQUE DE FACTURATION</p><h3>Vos paiements</h3></div></div>
    <div class="table-wrap"><table><thead><tr><th>RÉFÉRENCE</th><th>VOLUME</th><th>MONTANT</th><th>ÉTAT</th><th>DATE</th></tr></thead><tbody>
    @forelse($payments as $payment)
        <tr><td class="mono">{{ $payment->transaction_id }}</td><td>{{ $payment->sms_quantity }} SMS · {{ $payment->whatsapp_quantity }} WhatsApp</td><td><strong>{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</strong></td><td><span class="status-pill {{ $payment->status }}">{{ ['awaiting_approval'=>'Validation admin','paid'=>'Validé','failed'=>'Échec','rejected'=>'Refusé','pending'=>'Paiement en cours','created'=>'Créé'][$payment->status] ?? $payment->status }}</span></td><td>{{ $payment->paid_at?->format('d/m/Y · H:i') ?? $payment->created_at->format('d/m/Y · H:i') }}</td></tr>
    @empty
        <tr><td colspan="5" class="table-empty">Votre premier achat apparaîtra ici.</td></tr>
    @endforelse
    </tbody></table></div>
    @include('components.pagination', ['paginator' => $payments])
</section>
@endsection
