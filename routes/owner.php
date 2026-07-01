<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        Route::get('/dashboard', [\App\Http\Controllers\Owner\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/detail', [\App\Http\Controllers\Owner\DashboardController::class, 'detail'])->name('dashboard.detail');

        // Laporan
        Route::get('/reports', [\App\Http\Controllers\Owner\ReportController::class, 'index'])->name('reports');
        Route::get('/reports/export', [\App\Http\Controllers\Owner\ReportController::class, 'export'])->name('reports.export');

        // Activity Log
        // Route::get('/activity-logs', [\App\Http\Controllers\Owner\ActivityLogController::class, 'index'])->name('activity-logs');

    });
