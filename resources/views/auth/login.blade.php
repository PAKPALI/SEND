@extends('layouts.guest')
@section('content')
    <div class="auth-kicker">VOTRE ESPACE D’ENVOI</div>
    <h1>Ravi de vous revoir<span class="accent-dot">.</span></h1>
    <p class="auth-intro">Connectez-vous pour piloter vos campagnes SMS et WhatsApp.</p>
    @if($errors->any()) <div class="inline-error">{{ $errors->first() }}</div> @endif
    <form method="POST" action="{{ route('login.store') }}" class="stack-form">
        @csrf
        <label>Adresse email<input type="email" name="email" value="{{ old('email') }}" placeholder="vous@entreprise.com" required autofocus></label>
        <label>Mot de passe<div class="password-wrap"><input type="password" name="password" placeholder="Votre mot de passe" required><button type="button" data-password-toggle>Afficher</button></div></label>
        <label class="check-row"><input type="checkbox" name="remember"> <span>Se souvenir de moi</span></label>
        <button class="primary-button wide" type="submit">Ouvrir mon espace <span>→</span></button>
    </form>
    <p class="switch-auth">Pas encore de compte ? <a href="{{ route('register') }}">Créer mon compte</a></p>
@endsection
