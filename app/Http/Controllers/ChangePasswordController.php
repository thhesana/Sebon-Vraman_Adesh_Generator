<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $this->targetUsername($request)) {
            return redirect()->route('login');
        }

        return view('auth.change_password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $username = $this->targetUsername($request);
        if (! $username) {
            return redirect()->route('login');
        }

        $user = User::where('username', $username)->where('active', 'Y')->first();
        $old = $request->validated('old_password');

        if (! $user) {
            return back()->withErrors(['old_password' => 'User not found or account not active!']);
        }

        if ($old !== $user->password_hash && ! password_verify($old, $user->password_hash)) {
            return back()->withErrors(['old_password' => 'Old password is incorrect!']);
        }

        $user->password_hash = password_hash($request->validated('new_password'), PASSWORD_BCRYPT);
        $user->save();

        $request->session()->forget('password_change_username');

        return redirect()->route('login')->with('success', 'Password updated successfully! Please log in.');
    }

    /** A logged-in user, or someone who just logged in with a plain-text password. */
    private function targetUsername(Request $request): ?string
    {
        return Auth::user()?->username ?? $request->session()->get('password_change_username');
    }
}
