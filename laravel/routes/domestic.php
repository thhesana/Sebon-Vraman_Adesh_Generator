<?php

use App\Http\Controllers\DomesticTadaController;
use App\Http\Controllers\DomesticTadaReportController;
use Illuminate\Support\Facades\Route;

// Routes for the domestic area (loaded inside the 'legacy.auth' group from routes/web.php)

Route::get('/DomesticTadaView.php', [DomesticTadaController::class, 'index'])->name('domestic.index');
Route::match(['get', 'post'], '/AddDomesticTada.php', [DomesticTadaController::class, 'create'])->name('domestic.create');
Route::match(['get', 'post'], '/EditDomesticTada.php', [DomesticTadaController::class, 'edit'])->name('domestic.edit');
Route::get('/PrintDomesticTada.php', [DomesticTadaController::class, 'print'])->name('domestic.print');
Route::get('/DOMESTIC_TADAREPORT.php', [DomesticTadaReportController::class, 'index'])->name('domestic.report');
