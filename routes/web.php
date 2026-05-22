<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// ─── AUTH ROUTES ───────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── PROTECTED DASHBOARD ROUTES ────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Inventaris
    Route::get('/inventaris', [DashboardController::class, 'inventaris'])->name('inventaris');

    // GRN – Operasional
    Route::get('/grn', [DashboardController::class, 'grn'])->name('grn');
    Route::post('/grn', [DashboardController::class, 'grnSubmit'])->name('grn.submit');

    // Pabrik – Manufaktur
    Route::get('/pabrik', [DashboardController::class, 'pabrik'])->name('pabrik');
    Route::post('/pabrik', [DashboardController::class, 'pabrikSubmit'])->name('pabrik.submit');

    // Finance
    Route::get('/finance', [DashboardController::class, 'finance'])->name('finance');
    Route::post('/finance/upload', [DashboardController::class, 'financeUpload'])->name('finance.upload');

    // Sales
    Route::get('/sales', [DashboardController::class, 'sales'])->name('sales');
    Route::post('/sales', [DashboardController::class, 'salesSubmit'])->name('sales.submit');
});
