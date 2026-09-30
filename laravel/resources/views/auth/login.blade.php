<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vraman Adesh Generator - Login</title>
    <style>
        :root { --primary:#2563eb; --primary-dark:#1d4ed8; --secondary:#64748b; --text-dark:#1e293b; --background:#f1f5f9; --white:#fff; --error:#ef4444; --border-radius:12px; --shadow-md:0 10px 20px rgba(0,0,0,.19),0 6px 6px rgba(0,0,0,.23); --transition:all .3s cubic-bezier(.25,.8,.25,1); }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        body { background:var(--background); min-height:100vh; display:flex; align-items:center; justify-content:center;
            background-image:linear-gradient(45deg,rgba(37,99,235,.05) 25%,transparent 25%),linear-gradient(-45deg,rgba(37,99,235,.05) 25%,transparent 25%),linear-gradient(45deg,transparent 75%,rgba(37,99,235,.05) 75%),linear-gradient(-45deg,transparent 75%,rgba(37,99,235,.05) 75%);
            background-size:20px 20px; background-position:0 0,0 10px,10px -10px,-10px 0; }
        .login-container { width:100%; max-width:400px; padding:2rem; background:var(--white); border-radius:var(--border-radius); box-shadow:var(--shadow-md); position:relative; overflow:hidden; animation:fadeIn .5s ease forwards; }
        .background-shape { position:absolute; width:150px; height:150px; border-radius:50%; background:linear-gradient(45deg,var(--primary),var(--primary-dark)); top:-50px; right:-50px; z-index:0; opacity:.8; }
        .background-shape:nth-child(2) { width:100px; height:100px; bottom:-30px; left:-30px; top:auto; right:auto; }
        .logo-container { display:flex; justify-content:center; align-items:center; margin-bottom:1.5rem; position:relative; z-index:1; }
        .logo { max-width:200px; height:auto; transition:var(--transition); }
        .logo:hover { transform:scale(1.05); }
        h1 { color:var(--text-dark); font-size:1.5rem; text-align:center; margin-bottom:2rem; font-weight:600; position:relative; z-index:1; }
        h1:after { content:''; display:block; width:50px; height:3px; background:var(--primary); margin:.5rem auto 0; border-radius:3px; }
        .form-group { margin-bottom:1.5rem; position:relative; z-index:1; }
        .form-group label { display:block; color:var(--secondary); margin-bottom:.5rem; font-size:.875rem; font-weight:500; }
        .form-group input { width:100%; padding:.75rem 1rem; border:1px solid #e2e8f0; border-radius:var(--border-radius); font-size:1rem; background:#f8fafc; color:var(--text-dark); transition:var(--transition); }
        .form-group input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.2); }
        .form-group .icon { position:absolute; right:12px; top:36px; color:var(--secondary); }
        .error { color:var(--error); font-size:.875rem; margin-top:.5rem; margin-bottom:1rem; text-align:center; font-weight:500; }
        .btn { display:block; width:100%; padding:.75rem 1rem; border:none; background:linear-gradient(to right,var(--primary),var(--primary-dark)); color:var(--white); border-radius:var(--border-radius); font-size:1rem; font-weight:500; cursor:pointer; transition:var(--transition); position:relative; z-index:1; }
        .btn:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(37,99,235,.3); }
        @keyframes fadeIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        @media (max-width:576px) { .login-container { max-width:90%; padding:1.5rem; } }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="background-shape"></div>
        <div class="background-shape"></div>

        <div class="logo-container">
            <img class="logo" src="{{ asset('sebon_logo.png') }}" alt="SEBON Logo">
        </div>

        <h1>VRAMAN ADESH GENERATOR</h1>

        @if ($error)
            <div class="error">{{ $error }}</div>
        @endif

        <form method="POST" action="{{ url('/index.php') }}">
            @csrf
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Example: vraman_napit" value="{{ old('username') }}">
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>

            <button type="submit" name="login" class="btn">Login</button>
        </form>
    </div>
</body>
</html>
