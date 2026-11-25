<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#810000">
    <title>@yield('title', 'Dashboard') – {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <a href="{{ route(auth()->user()->homeRoute()) }}" style="text-decoration:none"><x-logo :size="44" word tone="maroon" tag="Membership platform" /></a>
        </div>
        <nav aria-label="Main">
            @can('dashboard.view')
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7.5" height="9" rx="2"/><rect x="13.5" y="3" width="7.5" height="5" rx="2"/><rect x="13.5" y="11" width="7.5" height="10" rx="2"/><rect x="3" y="15" width="7.5" height="6" rx="2"/></svg>
                Dashboard
            </a>
            @endcan
            @if (auth()->user()->isMember())
            <a href="{{ route('portal.index') }}" class="{{ request()->routeIs('portal.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="9" cy="11" r="2.2"/><path d="M5.8 16c.5-1.7 1.8-2.5 3.2-2.5s2.7.8 3.2 2.5M14.5 10h4M14.5 13.5h3"/></svg>
                My Membership
            </a>
            <a href="{{ route('portal.history') }}" class="{{ request()->routeIs('portal.history', 'portal.receipt') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/></svg>
                History &amp; Receipts
            </a>
            <a href="{{ route('portal.renew') }}" class="{{ request()->routeIs('portal.renew') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M20 12a8 8 0 1 1-2.4-5.7M20 4v4.5h-4.5"/></svg>
                Renew
            </a>
            @can('learning.access')
            <a href="{{ route('learn.index') }}" class="{{ request()->routeIs('learn.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M2.5 9.5L12 5l9.5 4.5L12 14z"/><path d="M6.5 11.8V16c0 1.4 2.5 3 5.5 3s5.5-1.6 5.5-3v-4.2M21.5 9.5V15"/></svg>
                Learning
            </a>
            @endcan
            @endif
            @can('members.view')
            <a href="{{ route('members.index') }}" class="{{ request()->routeIs('members.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><path d="M16 4.7a3.5 3.5 0 0 1 0 6.6M18 14.8c1.9.7 3.1 2.3 3.5 5.2"/></svg>
                Members
            </a>
            @endcan
            @can('catalog.view')
            <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M3 12.5V4.5A1.5 1.5 0 0 1 4.5 3h8L21 11.5a2 2 0 0 1 0 2.8l-6.7 6.7a2 2 0 0 1-2.8 0z"/><circle cx="8" cy="8" r="1.3"/></svg>
                Categories
            </a>
            @endcan
            @can('catalog.view')
            <a href="{{ route('levels.index') }}" class="{{ request()->routeIs('levels.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></svg>
                Levels
            </a>
            @endcan
            @can('transactions.view')
            <a href="{{ route('transactions.index') }}" class="{{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M4 8h14l-3.5-3.5M20 16H6l3.5 3.5"/></svg>
                Transactions
            </a>
            @endcan
            @can('payments.view')
            <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><rect x="2.5" y="5" width="19" height="14" rx="3"/><path d="M2.5 10h19M6.5 15h3"/></svg>
                Payments
            </a>
            @endcan
            @can('courses.view')
            <a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses.*', 'lessons.*', 'questions.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M2.5 9.5L12 5l9.5 4.5L12 14z"/><path d="M6.5 11.8V16c0 1.4 2.5 3 5.5 3s5.5-1.6 5.5-3v-4.2M21.5 9.5V15"/></svg>
                Courses
            </a>
            @endcan
            @can('users.manage')
            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M12 3l7 3v5c0 4.6-3 8.3-7 10-4-1.7-7-5.4-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
                Staff &amp; Roles
            </a>
            @endcan
        </nav>
        <div class="user">
            <div class="who">
                <span class="avatar" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div><small>Signed in as</small><strong>{{ auth()->user()->name }}</strong><span class="role-pill role-{{ auth()->user()->role }}" style="margin-top:.2rem">{{ auth()->user()->roleLabel() }}</span></div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-light btn-sm btn-block">Log out</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <button type="button" class="menu" id="menu-toggle" aria-label="Toggle menu" aria-controls="sidebar">&#9776;</button>
            <x-logo :size="32" word tone="maroon" />
        </div>
        <main class="content">
            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <strong>Please fix the following:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
