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
    </style>
    @stack('styles')
</head>
<body>
<div class="spinner-overlay" id="loadingSpinner"><div class="spinner"></div></div>

<div class="app-header">
    <h2>VRAMAN ADESH GENERATOR</h2>
    <div class="user-info">
        <p class="mb-0">User: <strong>{{ session('username') }}</strong></p>
        <form method="POST" action="{{ url('/logout.php') }}" style="display:inline;">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</div>
<ul class="nav nav-tabs bg-primary justify-content-center">
    <li class="nav-item"><a href="{{ url('/dashboard.php') }}" class="nav-link text-white">DASHBOARD</a></li>
    <li class="nav-item"><a href="{{ url('/InternationalVraman.php') }}" class="nav-link text-white" id="intlVramanTab">INT'L VRAMAN</a></li>
    <li class="nav-item"><a href="{{ url('/DomesticTadaView.php') }}" class="nav-link text-white">DOMESTIC VRAMAN</a></li>
    <li class="nav-item"><a href="{{ url('/usd_rate.php') }}" class="nav-link text-white">USD RATE</a></li>
    <li class="nav-item"><a href="{{ url('/orderlevel.php') }}" class="nav-link text-white">LEVEL_MASTER</a></li>
    <li class="nav-item"><a href="{{ url('/countrylist.php') }}" class="nav-link text-white">COUNTRY</a></li>
    <li class="nav-item"><a href="{{ url('/cityLIst.php') }}" class="nav-link text-white">CITY</a></li>
    <li class="nav-item"><a href="{{ url('/DistrictList.php') }}" class="nav-link text-white">DISTRICT</a></li>
    <li class="nav-item"><a href="{{ url('/employee_view.php') }}" class="nav-link text-white">EMPLOYEE</a></li>
    <li class="nav-item"><a href="{{ url('/fiscal_year.php') }}" class="nav-link text-white">FY</a></li>
    <li class="nav-item dropdown">
        <a href="#" class="nav-link text-white">REPORT</a>
        <ul class="dropdown-menu">
            <li><a href="{{ url('/DOMESTIC_TADAREPORT.php') }}">Domestic Vraman Report</a></li>
            <li><a href="{{ url('/INTERNATIONAL_TADAREPORT.php') }}">Int'l Vraman Report</a></li>
        </ul>
    </li>
</ul>

<main>
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
    fetch(@json(url('/usdforexudater.php'))).then(go).catch(go);
});
</script>
@if (session('alert'))
<script>alert(@json(session('alert')));</script>
@endif
@stack('scripts')
</body>
</html>
