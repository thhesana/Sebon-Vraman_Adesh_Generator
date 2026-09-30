<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ChangePasswordController extends Controller
{
    public function index(Request $request)
    {
        $error = null;
        $passwordUpdated = false;

        if ($request->isMethod('post') && $request->has('update_password')) {
            $username    = (string) $request->session()->get('username');
            $oldPassword = (string) $request->input('old_password');
            $newPassword = (string) $request->input('new_password');
            $confirm     = (string) $request->input('confirm_password');

            $user = User::where('username', $username)->where('active', 'Y')->first();

            if (! $user) {
                $error = 'User not found or account not active!';
            } elseif ($oldPassword !== $user->password_hash && ! password_verify($oldPassword, $user->password_hash)) {
                $error = 'Old password is incorrect!';
            } elseif ($newPassword !== $confirm) {
                $error = 'New password and confirmation do not match!';
            } else {
                $user->password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $user->save();
                $passwordUpdated = true;
            }
        }

        return view('auth.change_password', compact('error', 'passwordUpdated'));
    }
}
