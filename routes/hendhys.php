<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hendhys\ProductionController;
use App\Http\Controllers\Hendhys\PosController;
use App\Http\Controllers\Hendhys\PendingController;
use App\Http\Controllers\Hendhys\TransferRequestController;
use App\Http\Controllers\Hendhys\BranchRequestController;
use App\Http\Controllers\Hendhys\TransferToBranchController;
use App\Http\Controllers\Hendhys\ReturnController;
use App\Http\Controllers\Hendhys\StockController;

Route::middleware(['auth', 'check.entity:hendhys', 'check.branch', 'role:kasir_hendhys'])
    ->prefix('hendhys')
    ->name('hendhys.')
    ->group(function () {

        Route::get('/dashboard', fn () => view('hendhys.dashboard'))->name('dashboard');

        // Produksi (khusus pusat)
        Route::resource('productions', ProductionController::class)->except(['edit', 'update', 'destroy']);

        // POS (pusat & cabang)
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('/pos/{transaction}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');

        // Transaksi Pending
        Route::get('/pending', [PendingController::class, 'index'])->name('pending.index');
        Route::post('/pending', [PendingController::class, 'store'])->name('pending.store');
        Route::get('/pending/{pending}', [PendingController::class, 'show'])->name('pending.show');
        Route::delete('/pending/{pending}', [PendingController::class, 'destroy'])->name('pending.destroy');

        // Request ke Gudang (bahan baku)
        Route::resource('transfer-requests', TransferRequestController::class)->except(['edit', 'update', 'destroy']);

        // Request Cabang ke Pusat
        Route::resource('branch-requests', BranchRequestController::class)->except(['edit', 'update', 'destroy']);

        // Distribusi ke Cabang
        Route::resource('transfer-to-branch', TransferToBranchController::class)->except(['edit', 'update', 'destroy']);
        Route::post('transfer-to-branch/{transfer_to_branch}/receive', [TransferToBranchController::class, 'receive'])->name('transfer-to-branch.receive');

        // Retur dari Cabang
        Route::resource('returns', ReturnController::class)->except(['edit', 'update', 'destroy']);
        Route::post('returns/{return}/receive', [ReturnController::class, 'receive'])->name('returns.receive');

        // Stok Pusat & Cabang
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');

    });
