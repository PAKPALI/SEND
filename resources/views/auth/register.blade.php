@extends('layouts.guest')
@section('content')
    <div class="auth-kicker">COMMENCEZ EN QUELQUES SECONDES</div>
    <h1>Votre espace, enfin<span class="accent-dot">.</span></h1>
    <p class="auth-intro">Centralisez vos contacts et envoyez avec la puissance de Kprime.</p>
    @if($errors->any()) <div class="inline-error">{{ $errors->first() }}</div> @endif
    <form method="POST" action="{{ route('register.store') }}" class="stack-form">
        @csrf
        <label>Nom complet<input type="text" name="name" value="{{ old('name') }}" placeholder="Amina Kossi" required></label>
        <label>Adresse email<input type="email" name="email" value="{{ old('email') }}" placeholder="vous@entreprise.com" required></label>
        <div class="form-grid"><label>Pays<select name="country_code"><option value="TG" @selected(old('country_code', 'TG') === 'TG')>🇹🇬 Togo (+228)</option><option value="BJ" @selected(old('country_code') === 'BJ')>🇧🇯 Bénin (+229)</option><option value="CI" @selected(old('country_code') === 'CI')>🇨🇮 Côte d’Ivoire (+225)</option><option value="SN" @selected(old('country_code') === 'SN')>🇸🇳 Sénégal (+221)</option></select></label><label>Mot de passe<input type="password" name="password" placeholder="8 caractères minimum" required></label></div>
        <label>Confirmer le mot de passe<input type="password" name="password_confirmation" placeholder="Retapez votre mot de passe" required></label>
        <button class="primary-button wide" type="submit">Créer mon espace <span>→</span></button>
    </form>
    <p class="switch-auth">Vous avez déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
@endsection
