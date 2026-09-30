<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $user = User::where('username', $credentials['username'])->where('active', 'Y')->first();

        if (! $user) {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Invalid username or account not active']);
        }

        // Password still stored as plain text: force the user to set a hashed one first.
        if ($user->password_hash === $credentials['password']) {
            $request->session()->put('password_change_username', $user->username);

            return redirect()->route('password.change');
        }

        if (! password_verify($credentials['password'], $user->password_hash)) {
            return back()->withInput($request->only('username'))
                ->withErrors(['password' => 'Invalid password']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
