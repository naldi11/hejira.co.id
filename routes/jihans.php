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

        // Master Data (Scoped to Jihans)
        Route::prefix('master')->name('master.')->group(function () {
            Route::resource('suppliers', \App\Http\Controllers\Master\SupplierController::class)->except(['show']);
            Route::resource('customers', \App\Http\Controllers\Master\CustomerController::class)->except(['show']);
            Route::resource('products',  \App\Http\Controllers\Master\ProductController::class)->except(['show']);
            
            Route::get('categories',               [\App\Http\Controllers\Master\ProductCategoryController::class, 'index'])->name('categories.index');
            Route::post('categories',              [\App\Http\Controllers\Master\ProductCategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}',    [\App\Http\Controllers\Master\ProductCategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [\App\Http\Controllers\Master\ProductCategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('units',           [\App\Http\Controllers\Master\UnitController::class, 'index'])->name('units.index');
            Route::post('units',          [\App\Http\Controllers\Master\UnitController::class, 'store'])->name('units.store');
            Route::put('units/{unit}',    [\App\Http\Controllers\Master\UnitController::class, 'update'])->name('units.update');
            Route::delete('units/{unit}', [\App\Http\Controllers\Master\UnitController::class, 'destroy'])->name('units.destroy');

            Route::get('brands',            [\App\Http\Controllers\Master\BrandController::class, 'index'])->name('brands.index');
            Route::post('brands',           [\App\Http\Controllers\Master\BrandController::class, 'store'])->name('brands.store');
            Route::put('brands/{brand}',    [\App\Http\Controllers\Master\BrandController::class, 'update'])->name('brands.update');
            Route::delete('brands/{brand}', [\App\Http\Controllers\Master\BrandController::class, 'destroy'])->name('brands.destroy');
        });

        // Produksi Tortilla
        Route::resource('productions', ProductionController::class)->except(['edit', 'update', 'destroy']);

        // POS Kasir
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('/pos/{transaction}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');

        // Riwayat Transaksi
        Route::resource('transactions', \App\Http\Controllers\Jihans\TransactionController::class)->only(['index', 'show']);

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
