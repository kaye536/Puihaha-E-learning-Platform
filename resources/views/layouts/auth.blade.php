<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#810000">
    <title>@yield('title') – {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login-wrap">
    <section class="login-art" aria-hidden="true">
        <x-logo :size="48" word tag="Membership platform" />
        <div>
            <div class="pass-stack">
                <div class="pass-back"></div>
                <div class="pass-demo">
                    <x-logo :size="38" tone="maroon" />
                    <div class="pd-level">Your membership</div>
                    <div class="pd-no">MEM-2026-00001</div>
                </div>
            </div>
            <h2>Everything about your membership, in one place.</h2>
            <p>Check your status and expiry date, download receipts and request a renewal whenever you need to.</p>
        </div>
    </section>

    <section class="login-card-wrap">
        <div class="login-card">
            <h1>@yield('heading')</h1>
            <p class="sub">@yield('sub')</p>

            <div class="form-box">
                @if (session('status'))
                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif
                @yield('form')
            </div>
            <a class="back" href="{{ route('login') }}">&larr; Back to sign in</a>
        </div>
    </section>
</div>
</body>
</html>
