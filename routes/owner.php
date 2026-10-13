<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        Route::get('/dashboard', [\App\Http\Controllers\Owner\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/detail', [\App\Http\Controllers\Owner\DashboardController::class, 'detail'])->name('dashboard.detail');
        Route::get('/stock-movements/{movement}', [\App\Http\Controllers\Owner\StockMovementController::class, 'show'])->whereNumber('movement')->name('movements.show');
        Route::get('/dashboard/shift/{id}', [\App\Http\Controllers\Owner\DashboardController::class, 'shiftDetail'])->name('dashboard.shift');

        Route::get('/purchase-orders/{purchaseOrder}', [\App\Http\Controllers\Owner\OperationsController::class, 'purchaseOrder'])->name('po.show');
        Route::get('/production', [\App\Http\Controllers\Owner\OperationsController::class, 'index'])->name('production.index');
        Route::get('/production-writeoffs', [\App\Http\Controllers\Owner\ProductionWriteoffController::class, 'index'])->name('production-writeoffs.index');
        Route::post('/production-writeoffs', [\App\Http\Controllers\Owner\ProductionWriteoffController::class, 'store'])->name('production-writeoffs.store');
        Route::post('/production-writeoffs/bulk', [\App\Http\Controllers\Owner\ProductionWriteoffController::class, 'bulk'])->name('production-writeoffs.bulk');
        Route::post('/production-writeoffs/{writeoff}/reverse', [\App\Http\Controllers\Owner\ProductionWriteoffController::class, 'reverse'])->whereNumber('writeoff')->name('production-writeoffs.reverse');
        Route::get('/production/{entity}/{id}', [\App\Http\Controllers\Owner\OperationsController::class, 'production'])->whereIn('entity', ['jihans', 'hendhys'])->whereNumber('id')->name('production.show');

        // Laporan
        Route::get('/reports', [\App\Http\Controllers\Owner\ReportController::class, 'index'])->name('reports');
        Route::get('/reports/export', [\App\Http\Controllers\Owner\ReportController::class, 'export'])->name('reports.export');

        // Activity Log
        // Route::get('/activity-logs', [\App\Http\Controllers\Owner\ActivityLogController::class, 'index'])->name('activity-logs');

    });
