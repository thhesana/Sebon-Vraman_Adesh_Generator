<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsdRateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy-parity routes
|--------------------------------------------------------------------------
|
| URLs intentionally mirror the legacy Vraman_Adesh_Generator's literal .php
| filenames/query strings so every link, bookmark and MIS shortcut keeps
| working. Each functional area lives in its own route file.
|
*/

// Public
Route::match(['get', 'post'], '/', [AuthController::class, 'index']);
Route::match(['get', 'post'], '/index.php', [AuthController::class, 'index'])->name('login');
Route::match(['get', 'post'], '/logout.php', [AuthController::class, 'logout'])->name('logout');

// Legacy quirk: the change-password page has no logged-in check (plain-text-password users land here).
Route::match(['get', 'post'], '/ChangePassword.php', [ChangePasswordController::class, 'index']);
Route::match(['get', 'post'], '/changepassword.php', [ChangePasswordController::class, 'index']);

Route::middleware('legacy.auth')->group(function () {
    Route::get('/dashboard.php', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/usd_rate.php', [UsdRateController::class, 'index'])->name('usd.rates');
    Route::match(['get', 'post'], '/usdforexudater.php', [UsdRateController::class, 'converter'])->name('usd.converter');

    require __DIR__.'/masters.php';
    require __DIR__.'/domestic.php';
    require __DIR__.'/international.php';
});
