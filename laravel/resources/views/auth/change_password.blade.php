<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Please Change your Password</title>
    <link rel="icon" href="{{ asset('sebon_logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/auth/change_password.css') }}" rel="stylesheet">
</head>
<body>
    <main class="auth-page">
        <div class="auth-card card">
            <div class="card-body">
                <div class="auth-brand">
                    <img class="auth-logo" src="{{ asset('sebon_logo.png') }}" alt="SEBON logo">
                    <h1>Change your password</h1>
                    <p class="text-muted mb-0">Please change your password. The new password will be stored encrypted (hashed).</p>
                </div>

                <x-auth-messages tag="p" />

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Old Password</label>
                        <input type="password" id="old_password" name="old_password" class="form-control" required autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required autocomplete="new-password">
                    </div>

                    <div class="mb-4">
                        <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-control" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update Password</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
