<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Port of the legacy header.php gate: every page except login/change-password
 * required $_SESSION['loggedin'] === true.
 */
class EnsureLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('loggedin') !== true) {
            return redirect('/index.php');
        }

        return $next($request);
    }
}
