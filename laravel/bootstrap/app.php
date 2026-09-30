<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'legacy.auth' => \App\Http\Middleware\EnsureLoggedIn::class,
        ]);

        // The header's "INT'L VRAMAN" tab calls usdforexudater.php with a plain fetch()
        // (no CSRF token) to refresh the rate before navigating, exactly like the legacy app.
        $middleware->validateCsrfTokens(except: [
            'usdforexudater.php',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
