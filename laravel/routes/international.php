<?php

use App\Http\Controllers\InternationalTadaController;
use App\Http\Controllers\InternationalTadaReportController;
use Illuminate\Support\Facades\Route;

// Routes for the international area (loaded inside the 'legacy.auth' group from routes/web.php)
// Legacy URLs are kept literally (plus lower-case variants so bookmarks typed either way keep working).

Route::get('/InternationalVraman.php', [InternationalTadaController::class, 'index'])->name('international.tada.index');
Route::get('/internationalvraman.php', [InternationalTadaController::class, 'index']);

Route::get('/add_international_tada.php', [InternationalTadaController::class, 'create'])->name('international.tada.create');
Route::post('/add_international_tada.php', [InternationalTadaController::class, 'store'])->name('international.tada.store');

Route::get('/edit_international_tada.php', [InternationalTadaController::class, 'edit'])->name('international.tada.edit');
Route::post('/edit_international_tada.php', [InternationalTadaController::class, 'update'])->name('international.tada.update');

Route::get('/print_international_tada.php', [InternationalTadaController::class, 'print'])->name('international.tada.print');

Route::get('/INTL_HOSTORYTADA.php', [InternationalTadaReportController::class, 'history'])->name('international.tada.history');
Route::get('/intl_hostorytada.php', [InternationalTadaReportController::class, 'history']);

Route::get('/INTERNATIONAL_TADAREPORT.php', [InternationalTadaReportController::class, 'report'])->name('international.tada.report');
Route::get('/international_tadareport.php', [InternationalTadaReportController::class, 'report']);
