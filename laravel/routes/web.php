<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DomesticTadaController;
use App\Http\Controllers\DomesticTadaReportController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\InternationalTadaController;
use App\Http\Controllers\InternationalTadaReportController;
use App\Http\Controllers\LevelMasterController;
use App\Http\Controllers\UsdRateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Old *.php URLs from the pre-Laravel application are redirected to the
| routes below by routes/legacy.php, so existing links keep working.
|
*/

Route::get('/', fn () => redirect()->route('login'));

// ---- Authentication ------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Reachable without a session login: users whose password is still plain text land here.
Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('password.change');
Route::put('/change-password', [ChangePasswordController::class, 'update'])->name('password.update');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---- Application ---------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // USD rate
    Route::get('/usd-rates', [UsdRateController::class, 'index'])->name('usd.rates');
    Route::match(['get', 'post'], '/usd-converter', [UsdRateController::class, 'converter'])->name('usd.converter');

    // Master data
    Route::get('/levels', [LevelMasterController::class, 'index'])->name('levels.index');

    Route::resource('countries', CountryController::class)->only(['index', 'edit', 'update']);
    Route::get('/countries/{country}/cities', [CityController::class, 'byCountry'])->name('countries.cities');

    Route::resource('cities', CityController::class)->except(['show', 'destroy']);
    Route::resource('districts', DistrictController::class)->only(['index', 'create', 'store']);
    Route::resource('employees', EmployeeController::class)->except(['show', 'destroy']);
    Route::resource('fiscal-years', FiscalYearController::class)
        ->except(['show', 'destroy'])
        ->names('fiscal_years')
        ->parameters(['fiscal-years' => 'fiscal_year']);

    // Domestic TADA (a "batch" is identified by its DBATCH### id)
    Route::get('/domestic-tada/report', [DomesticTadaReportController::class, 'index'])->name('domestic.report');
    Route::get('/domestic-tada/{batch}/print', [DomesticTadaController::class, 'print'])->name('domestic.print');
    Route::resource('domestic-tada', DomesticTadaController::class)
        ->except(['show', 'destroy'])
        ->names('domestic')
        ->parameters(['domestic-tada' => 'batch']);

    // International TADA
    Route::get('/international-tada/history', [InternationalTadaReportController::class, 'history'])->name('international.history');
    Route::get('/international-tada/report', [InternationalTadaReportController::class, 'report'])->name('international.report');
    Route::get('/international-tada/{batch}/print', [InternationalTadaController::class, 'print'])->name('international.print');
    Route::resource('international-tada', InternationalTadaController::class)
        ->except(['show', 'destroy'])
        ->names('international')
        ->parameters(['international-tada' => 'batch']);
});

require __DIR__.'/legacy.php';
