<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Jihans\ProductionController;
use App\Http\Controllers\Jihans\PosController;
use App\Http\Controllers\Jihans\PendingController;
use App\Http\Controllers\Jihans\TransferRequestController;
use App\Http\Controllers\Jihans\StockController;

Route::middleware(['auth', 'check.entity:jihans', 'role:kasir_jihans|admin_jihans'])
    ->prefix('jihans')
    ->name('jihans.')
    ->group(function () {

        Route::get('/dashboard', fn () => view('jihans.dashboard'))->name('dashboard');

        // Produksi Tortilla
        Route::resource('productions', ProductionController::class)->except(['edit', 'update', 'destroy']);

        // POS Kasir
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('/pos/{transaction}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');

        // Transaksi Pending
        Route::get('/pending', [PendingController::class, 'index'])->name('pending.index');
        Route::post('/pending', [PendingController::class, 'store'])->name('pending.store');
        Route::get('/pending/{pending}', [PendingController::class, 'show'])->name('pending.show');
        Route::delete('/pending/{pending}', [PendingController::class, 'destroy'])->name('pending.destroy');

        // Request ke Gudang
        Route::resource('transfer-requests', TransferRequestController::class)->except(['edit', 'update', 'destroy']);

        // Stok
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');

    });
