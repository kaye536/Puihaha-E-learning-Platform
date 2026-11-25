<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#810000">
    <title>@yield('title', 'Apply for membership') – {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="lp">
<header class="lp-nav">
    <div class="lp-wrap">
        <a href="{{ url('/') }}" style="text-decoration:none"><x-logo :size="36" word tone="maroon" /></a>
        <nav aria-label="Main"></nav>
        <div class="cta">
            <a class="login" href="{{ route('login') }}">Staff log in</a>
        </div>
    </div>
</header>

<main class="lp-wrap pub-main">
    @yield('content')
</main>

<footer class="lp-foot">
    <div class="lp-wrap">
        <x-logo :size="30" word tone="maroon" />
        <span>&copy; {{ date('Y') }} {{ config('membership.organization') }}</span>
    </div>
</footer>
</body>
</html>
