<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function index(Request $request)
    {
        $error = null;

        if ($request->isMethod('post') && $request->has('login')) {
            $username = (string) $request->input('username');
            $password = (string) $request->input('password');

            $user = User::where('username', $username)->where('active', 'Y')->first();

            if (! $user) {
                $error = 'Invalid username or account not active';
            } elseif ($user->password_hash === $password) {
                // Password still stored as plain text: force the user to set a hashed one.
                $request->session()->put('username', $username);

                return redirect('/changepassword.php');
            } elseif (password_verify($password, $user->password_hash)) {
                $request->session()->regenerate();
                $request->session()->put([
                    'loggedin' => true,
                    'user_id'  => $user->user_id,
                    'username' => $user->username,
                    'role'     => $user->role,
                ]);

                // Legacy sent admins to admin_dashboard.php, which never existed; everyone lands here.
                return redirect('/dashboard.php');
            } else {
                $error = 'Invalid password';
            }
        }

        return view('auth.login', ['error' => $error]);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/index.php');
    }
}
