@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">NOUVEL ENVOI</p>
        <h2>Composez votre campagne.</h2>
        <p class="page-subtitle">SEND s’occupe de l’envoi en arrière-plan.</p>
    </div>
    <a class="subtle-link" href="{{ route('campaigns.index') }}">← Retour aux campagnes</a>
</div>

<div class="composer-layout">
    <form method="POST" action="{{ route('campaigns.store') }}" class="panel composer-form" data-campaign-form data-sms-credits="{{ auth()->user()->sms_credits }}" data-whatsapp-credits="{{ auth()->user()->whatsapp_credits }}" data-pending-sms="{{ $pendingCredits['sms'] }}" data-pending-whatsapp="{{ $pendingCredits['whatsapp'] }}" data-loading-title="Préparation de la campagne" data-loading-message="Vérification du quota et mise en file des messages…">
        @csrf
        <div class="form-section">
            <div class="step-badge">01</div>
            <div class="step-content">
                <p class="eyebrow">IDENTITÉ</p>
                <h3>Donnez un nom à votre campagne</h3>
                <label>Nom interne
                    <input name="name" value="{{ old('name') }}" placeholder="Ex. Offre rentrée 2026" required>
                </label>
            </div>
        </div>

        <div class="form-section">
            <div class="step-badge">02</div>
            <div class="step-content">
                <p class="eyebrow">AUDIENCE & CANAL</p>
                <h3>Qui voulez-vous atteindre ?</h3>
                <label>Groupe de contacts
                    <select name="contact_group_id" required data-campaign-group>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" data-contact-count="{{ $group->contacts_count }}" @selected(old('contact_group_id', request('group')) == $group->id)>{{ $group->name }} · {{ $group->contacts_count }} contact(s)</option>
                        @endforeach
                    </select>
                </label>
                <div class="channel-choice">
                    <label class="channel-option">
                        <input type="radio" name="channel" value="sms" data-campaign-channel @checked(old('channel', 'sms') === 'sms')>
                        <span class="choice-card"><b class="choice-icon sms">✉</b><strong>SMS</strong><small>Direct & universel</small></span>
                    </label>
                    <label class="channel-option">
                        <input type="radio" name="channel" value="whatsapp" data-campaign-channel @checked(old('channel') === 'whatsapp')>
                        <span class="choice-card"><b class="choice-icon whatsapp">◉</b><strong>WhatsApp</strong><small>Riche & engageant</small></span>
                    </label>
                </div>
                <div class="quota-approval-notice" data-quota-notice hidden></div>
            </div>
        </div>

        <div class="form-section">
            <div class="step-badge">03</div>
            <div class="step-content">
                <p class="eyebrow">MESSAGE</p>
                <h3>Écrivez votre message</h3>
                <label>Titre WhatsApp <span class="muted">(facultatif)</span>
                    <input name="title" value="{{ old('title') }}" placeholder="Ex. Une offre pour vous">
                </label>
                <label>Contenu
                    <textarea name="message" rows="6" maxlength="4096" data-counter required placeholder="Bonjour, votre message ici…">{{ old('message') }}</textarea>
                    <small class="char-counter"><span data-char-count>0</span>/4096 caractères</small>
                </label>
            </div>
        </div>

        <div class="composer-actions">
            <a class="secondary-button" href="{{ route('campaigns.index') }}">Annuler</a>
            <button class="primary-button" type="submit" data-campaign-submit>Mettre en file <span>→</span></button>
        </div>
    </form>

    <aside class="panel preview-panel">
        <p class="eyebrow">APERÇU</p>
        <h3>Votre message</h3>
        <div class="preview-device">
            <div class="preview-header"><span class="avatar avatar-tiny">S</span><div><strong>SEND</strong><small>maintenant</small></div></div>
            <div class="preview-bubble" data-preview-message>Votre message apparaîtra ici.</div>
        </div>
        <div class="quota-hint"><span>◈</span><div><strong>Envoi sécurisé</strong><p>Chaque destinataire sera traité par un job dédié et enregistré dans votre historique.</p></div></div>
    </aside>
</div>
@endsection
