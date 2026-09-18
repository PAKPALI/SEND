<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SEND' }} · SEND</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
    <div class="global-loader" id="global-loader" aria-hidden="true">
        <div class="loader-card" role="status" aria-live="polite">
            <div class="loader-mark"><span></span><span></span><span></span></div>
            <strong data-loader-title>Chargement en cours</strong>
            <small data-loader-message>Préparation de votre espace…</small>
        </div>
    </div>
    <aside class="sidebar" id="sidebar">
        <div class="brand"><span class="brand-mark">S</span><span>SEND</span></div>
        <div class="workspace-switcher"><span class="avatar avatar-small">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span><small>ESPACE PERSONNEL</small><strong>{{ Str::limit(auth()->user()->name, 21) }}</strong></span><span class="chevron">⌄</span></div>
        <nav class="nav-stack">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>⌂</span>Vue d’ensemble</a>
            <div class="nav-label">CONTACTS</div>
            <a class="nav-link {{ request()->routeIs('contacts.*') ? 'active' : '' }}" href="{{ route('contacts.index') }}"><span>◌</span>Carnet de contacts</a>
            <a class="nav-link {{ request()->routeIs('groups.*') ? 'active' : '' }}" href="{{ route('groups.index') }}"><span>◎</span>Groupes</a>
            <div class="nav-label">MESSAGERIE</div>
            <a class="nav-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}" href="{{ route('campaigns.index') }}"><span>✦</span>Campagnes</a>
            <a class="nav-link {{ request()->routeIs('history.*') ? 'active' : '' }}" href="{{ route('history.index') }}"><span>↺</span>Historique</a>
            <div class="nav-label">COMPTE</div>
            <a class="nav-link {{ request()->routeIs('quota.*') ? 'active' : '' }}" href="{{ route('quota.index') }}"><span>◈</span>Crédits & paiements</a>
            @if(auth()->user()->isAdmin())
                <div class="nav-label">ADMINISTRATION</div>
                <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.quota.index') }}"><span>✓</span>Valider les quotas</a>
            @endif
        </nav>
        <div class="sidebar-bottom">
            <div class="support-card"><span class="support-icon">✦</span><div><strong>Besoin d’aide ?</strong><small>Notre équipe est là.</small></div><span>›</span></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link logout-button" type="submit"><span>↪</span>Se déconnecter</button></form>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu" type="button" data-sidebar-toggle>☰</button>
            <div><p class="eyebrow">{{ now()->translatedFormat('l d F Y') }}</p><h1>{{ $heading ?? 'Bonjour, '.Str::before(auth()->user()->name, ' ') }} <span class="wave">✦</span></h1></div>
            <div class="topbar-actions"><a class="icon-button" href="{{ route('quota.index') }}" title="Acheter des crédits">＋</a><div class="notification-dot">•</div><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span></div>
        </header>
        @if(session('success')) <div class="flash success">✓ <span>{{ session('success') }}</span><button type="button" data-dismiss>×</button></div> @endif
        @if(session('info')) <div class="flash info">i <span>{{ session('info') }}</span><button type="button" data-dismiss>×</button></div> @endif
        @if($errors->any()) <div class="flash danger">! <span>{{ $errors->first() }}</span><button type="button" data-dismiss>×</button></div> @endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>
</body>
</html>
