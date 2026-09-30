<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vraman Adesh Generator - Login</title>
    <link rel="icon" href="{{ asset('sebon_logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/auth/login.css') }}" rel="stylesheet">
</head>
<body>
    <main class="auth-page">
        <div class="auth-card card">
            <div class="card-body">
                <div class="auth-brand">
                    <img class="auth-logo" src="{{ asset('sebon_logo.png') }}" alt="SEBON logo">
                    <h1>Vraman Adesh Generator</h1>
                    <p class="text-muted mb-0">Securities Board of Nepal &mdash; sign in to continue</p>
                </div>

                <x-auth-messages success />

                <form method="POST" action="{{ route('login.attempt') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required autofocus
                               autocomplete="username" placeholder="Example: vraman_napit" value="{{ old('username') }}">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                    </div>

                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
