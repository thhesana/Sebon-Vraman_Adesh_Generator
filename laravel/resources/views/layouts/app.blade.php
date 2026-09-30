<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SEBON MIS')</title>
    <link rel="icon" href="{{ asset('sebon_logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<div class="spinner-overlay" id="loadingSpinner"><div class="spinner"></div></div>

<header class="topbar">
    <div class="topbar-inner">
        <a href="{{ route('dashboard') }}" class="brand">
            <img src="{{ asset('sebon_logo.png') }}" alt="SEBON">
            <span class="brand-title">Vraman Adesh Generator
                <span class="brand-sub">Securities Board of Nepal</span>
            </span>
        </a>
        <div class="topbar-user">
            <span class="user-chip">
                <span class="user-avatar">{{ mb_substr(auth()->user()->username, 0, 1) }}</span>
                <span class="user-name">{{ auth()->user()->username }}</span>
            </span>
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>
</header>

@include('layouts._nav')

<main>
    @include('layouts._flash')
    @yield('content')
</main>

<footer class="app-footer">
    <p>&copy; {{ date('Y') }} Securities Board of Nepal (SEBON)</p>
    <p>Developed &amp; Maintained by IT SECTION, SEBON</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
