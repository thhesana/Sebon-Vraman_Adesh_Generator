<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SEBON MIS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body { min-height: 100%; }
        body { background: #f9f9f9; font-family: 'Arial', sans-serif; display: flex; flex-direction: column; min-height: 100vh; }
        main { flex: 1 0 auto; }
        .app-header { background: #007bff; color: white; padding: 30px 15px; text-align: center; }
        .app-header h2 { margin-bottom: 20px; }
        .user-info { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 10px; }
        .logout-btn { background: red; border: none; padding: 8px 15px; color: white; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .logout-btn:hover { background: darkred; }
        .nav-tabs .nav-link { color: white; }
        .nav-tabs .nav-link:hover, .nav-tabs .nav-link.active { background: #0056b3; }
        .spinner-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); z-index: 9999; justify-content: center; align-items: center; }
        .spinner-overlay.active { display: flex; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .nav-item { position: relative; }
        .dropdown-menu { display: none; position: absolute; top: 100%; left: 0; background: #fff; min-width: 220px; padding: 0; margin: 0; list-style: none; box-shadow: 0 4px 10px rgba(0,0,0,.2); z-index: 999; }
        .dropdown-menu li a { display: block; padding: 10px 15px; color: #000; text-decoration: none; }
        .dropdown-menu li a:hover { background: #f2f2f2; }
        .nav-item:hover .dropdown-menu { display: block; }
        .app-footer { background-color: #002147; color: white; text-align: center; padding: 12px 0; font-size: 14px; margin-top: auto; box-shadow: 0 -1px 4px rgba(0,0,0,.2); }
        .app-footer p { margin: 2px 0; }
        @media print { .app-header, .nav-tabs, .app-footer, .flash-messages { display: none !important; } }
    </style>
    @stack('styles')
</head>
<body>
<div class="spinner-overlay" id="loadingSpinner"><div class="spinner"></div></div>

<div class="app-header">
    <h2>VRAMAN ADESH GENERATOR</h2>
    <div class="user-info">
        <p class="mb-0">User: <strong>{{ auth()->user()->username }}</strong></p>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</div>
<ul class="nav nav-tabs bg-primary justify-content-center">
    <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}">DASHBOARD</a></li>
    <li class="nav-item"><a href="{{ route('international.index') }}" class="nav-link text-white {{ request()->routeIs('international.index', 'international.create', 'international.edit') ? 'active' : '' }}" id="intlVramanTab">INT'L VRAMAN</a></li>
    <li class="nav-item"><a href="{{ route('domestic.index') }}" class="nav-link text-white {{ request()->routeIs('domestic.index', 'domestic.create', 'domestic.edit') ? 'active' : '' }}">DOMESTIC VRAMAN</a></li>
    <li class="nav-item"><a href="{{ route('usd.rates') }}" class="nav-link text-white {{ request()->routeIs('usd.*') ? 'active' : '' }}">USD RATE</a></li>
    <li class="nav-item"><a href="{{ route('levels.index') }}" class="nav-link text-white {{ request()->routeIs('levels.*') ? 'active' : '' }}">LEVEL_MASTER</a></li>
    <li class="nav-item"><a href="{{ route('countries.index') }}" class="nav-link text-white {{ request()->routeIs('countries.*') ? 'active' : '' }}">COUNTRY</a></li>
    <li class="nav-item"><a href="{{ route('cities.index') }}" class="nav-link text-white {{ request()->routeIs('cities.*') ? 'active' : '' }}">CITY</a></li>
    <li class="nav-item"><a href="{{ route('districts.index') }}" class="nav-link text-white {{ request()->routeIs('districts.*') ? 'active' : '' }}">DISTRICT</a></li>
    <li class="nav-item"><a href="{{ route('employees.index') }}" class="nav-link text-white {{ request()->routeIs('employees.*') ? 'active' : '' }}">EMPLOYEE</a></li>
    <li class="nav-item"><a href="{{ route('fiscal_years.index') }}" class="nav-link text-white {{ request()->routeIs('fiscal_years.*') ? 'active' : '' }}">FY</a></li>
    <li class="nav-item dropdown">
        <a href="#" class="nav-link text-white">REPORT</a>
        <ul class="dropdown-menu">
            <li><a href="{{ route('domestic.report') }}">Domestic Vraman Report</a></li>
            <li><a href="{{ route('international.report') }}">Int'l Vraman Report</a></li>
        </ul>
    </li>
</ul>

<main>
    @include('layouts._flash')
    @yield('content')
</main>

<footer class="app-footer">
    <p>&copy; {{ date('Y') }} Securities Board of Nepal (SEBON)</p>
    <p>Developed &amp; Maintained by IT SECTION, SEBON</p>
</footer>

<script>
// Refresh the NRB rate in the background before opening the international module.
document.getElementById('intlVramanTab').addEventListener('click', function (e) {
    e.preventDefault();
    const target = this.href;
    const go = () => { window.location.href = target; };
    document.getElementById('loadingSpinner').classList.add('active');
    fetch(@json(route('usd.converter'))).then(go).catch(go);
});
</script>
@stack('scripts')
</body>
</html>
