<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Please Change your Password</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,.1); width: 100%; max-width: 400px; text-align: center; }
        .login-container img { max-width: 150px; height: auto; margin-bottom: 20px; }
        .login-container h1 { margin-bottom: 20px; font-size: 24px; color: #333; }
        .login-container label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .login-container input[type="password"] { width: calc(100% - 22px); padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; }
        .login-container button { padding: 10px 20px; font-size: 16px; color: #fff; background-color: #007BFF; border: none; border-radius: 4px; cursor: pointer; transition: background-color .3s ease; }
        .login-container button:hover { background-color: #0056b3; }
        .login-container p.error { color: red; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="{{ asset('sebon_logo.png') }}" alt="Office Logo">
        <h1>Please Change your password as new Password will be encrypted in hash.</h1>
        @foreach ($errors->all() as $message)
            <p class="error">{{ $message }}</p>
        @endforeach
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')
            <label for="old_password">Old Password:</label>
            <input type="password" id="old_password" name="old_password" required><br>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required><br>

            <label for="new_password_confirmation">Confirm New Password:</label>
            <input type="password" id="new_password_confirmation" name="new_password_confirmation" required><br>

            <button type="submit">Update Password</button>
        </form>
    </div>
</body>
</html>
