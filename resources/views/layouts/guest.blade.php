<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Bienvenue' }} · SEND</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-shell">
    <div class="global-loader" id="global-loader" aria-hidden="true">
        <div class="loader-card" role="status" aria-live="polite">
            <div class="loader-mark"><span></span><span></span><span></span></div>
            <strong data-loader-title>Chargement en cours</strong>
            <small data-loader-message>Préparation de votre espace…</small>
        </div>
    </div>
    <div class="guest-orb orb-one"></div><div class="guest-orb orb-two"></div>
    <div class="guest-brand"><span class="brand-mark">S</span><span>SEND</span></div>
    <div class="guest-card">@yield('content')</div>
    <p class="guest-foot">Messagerie de groupe simple, élégante et fiable.</p>
</body>
</html>
