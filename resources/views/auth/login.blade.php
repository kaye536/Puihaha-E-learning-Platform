<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#810000">
    <title>Sign in – {{ config('app.name') }}</title>
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
                    <div class="pd-level">Gold member</div>
                    <div class="pd-no">MEM-2026-00001</div>
                </div>
            </div>
            <h2>Every member, on the register.</h2>
            <p>Applications, renewals, payments and receipts, kept in one place from the first sign-up to the last renewal.</p>
        </div>
    </section>

    <section class="login-card-wrap">
        <div class="login-card">
            <h1>Welcome back</h1>
            <p class="sub">Staff and members: sign in to your account.</p>

            <div class="form-box">
                @if (session('status'))
                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}">
                    @csrf
                    <div class="form-group" style="margin-bottom:1rem">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>
                    <div class="form-group" style="margin-bottom:1rem">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
                        <label class="check" style="margin:0">
                            <input type="checkbox" name="remember" value="1"> Remember me
                        </label>
                        <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-block">Sign in</button>
                </form>
            </div>
            <a class="back" href="{{ url('/') }}">&larr; Back to home</a>
        </div>
    </section>
</div>
</body>
</html>
