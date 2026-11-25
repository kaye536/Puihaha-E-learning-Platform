<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied – {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="lp">
<main class="lp-wrap pub-main">
    <section class="thanks-card">
        <div class="thanks-icon denied" aria-hidden="true">&#128274;</div>
        <span class="eyebrow">Error 403</span>
        <h1>Access denied</h1>
        @auth
            <p class="lead">Your role (<strong>{{ auth()->user()->roleLabel() }}</strong>) isn’t allowed to do that.</p>
            <p class="hint">If you think this is a mistake, please ask an administrator to update your role.</p>
        @else
            <p class="lead">You don’t have permission to open this page.</p>
        @endauth
        <div class="actions" style="justify-content:center;margin-top:1.4rem">
            <a href="{{ auth()->check() ? route(auth()->user()->homeRoute()) : route('login') }}" class="btn btn-lg">{{ auth()->check() ? (auth()->user()->isMember() ? 'Back to my membership' : 'Back to dashboard') : 'Go to sign in' }}</a>
        </div>
    </section>
</main>
</body>
</html>
