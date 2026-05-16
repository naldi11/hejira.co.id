<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        Route::get('/dashboard', [\App\Http\Controllers\Owner\DashboardController::class, 'index'])->name('dashboard');

        // Dashboard per entitas
        Route::get('/gudang', [\App\Http\Controllers\Owner\GudangDashboardController::class, 'index'])->name('gudang');
        Route::get('/jihans', [\App\Http\Controllers\Owner\JihansDashboardController::class, 'index'])->name('jihans');
        Route::get('/hendhys', [\App\Http\Controllers\Owner\HendhysDashboardController::class, 'index'])->name('hendhys');

        // Laporan
        Route::get('/reports', [\App\Http\Controllers\Owner\ReportController::class, 'index'])->name('reports');

        // Activity Log
        // Route::get('/activity-logs', [\App\Http\Controllers\Owner\ActivityLogController::class, 'index'])->name('activity-logs');

    });
