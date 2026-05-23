<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hendhys\ProductionController;
use App\Http\Controllers\Hendhys\PosController;
use App\Http\Controllers\Hendhys\PendingController;

use App\Http\Controllers\Hendhys\BranchRequestController;
use App\Http\Controllers\Hendhys\TransferToBranchController;
use App\Http\Controllers\Hendhys\ReturnController;
use App\Http\Controllers\Hendhys\StockController;

Route::middleware(['auth', 'check.entity:hendhys', 'check.branch', 'role:kasir_hendhys'])
    ->prefix('hendhys')
    ->name('hendhys.')
    ->group(function () {

        Route::get('/dashboard', fn() => view('hendhys.dashboard'))->name('dashboard');

        // Master Data (Scoped to Hendhys)
        Route::prefix('master')->name('master.')->group(function () {
            Route::resource('suppliers', \App\Http\Controllers\Master\SupplierController::class)->except(['show']);
            Route::get('products/template', [\App\Http\Controllers\Master\ProductController::class, 'downloadTemplate'])->name('products.template');
            Route::post('products/import', [\App\Http\Controllers\Master\ProductController::class, 'import'])->name('products.import');
            Route::resource('products', \App\Http\Controllers\Master\ProductController::class)->except(['show']);

            Route::get('categories', [\App\Http\Controllers\Master\ProductCategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [\App\Http\Controllers\Master\ProductCategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [\App\Http\Controllers\Master\ProductCategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [\App\Http\Controllers\Master\ProductCategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('units', [\App\Http\Controllers\Master\UnitController::class, 'index'])->name('units.index');
            Route::post('units', [\App\Http\Controllers\Master\UnitController::class, 'store'])->name('units.store');
            Route::put('units/{unit}', [\App\Http\Controllers\Master\UnitController::class, 'update'])->name('units.update');
            Route::delete('units/{unit}', [\App\Http\Controllers\Master\UnitController::class, 'destroy'])->name('units.destroy');

            Route::get('brands', [\App\Http\Controllers\Master\BrandController::class, 'index'])->name('brands.index');
            Route::post('brands', [\App\Http\Controllers\Master\BrandController::class, 'store'])->name('brands.store');
            Route::put('brands/{brand}', [\App\Http\Controllers\Master\BrandController::class, 'update'])->name('brands.update');
            Route::delete('brands/{brand}', [\App\Http\Controllers\Master\BrandController::class, 'destroy'])->name('brands.destroy');

            Route::resource('payment-methods', \App\Http\Controllers\Master\PaymentMethodController::class)->except(['show']);
        });

        // Produksi (khusus pusat)
        Route::resource('productions', ProductionController::class)->except(['edit', 'update', 'destroy']);

        // POS (pusat & cabang)
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::get('/pos/held-stock', [PosController::class, 'heldStock'])->name('pos.held-stock');
        Route::get('/pos/customer-search', [PosController::class, 'customerSearch'])->name('pos.customer-search');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('/pos/{transaction}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');

        // Riwayat Transaksi
        Route::resource('transactions', \App\Http\Controllers\Hendhys\TransactionController::class)->only(['index', 'show']);

        // Transaksi Pending
        Route::get('/pending', [PendingController::class, 'index'])->name('pending.index');
        Route::post('/pending', [PendingController::class, 'store'])->name('pending.store');
        Route::get('/pending/{pending}', [PendingController::class, 'show'])->name('pending.show');
        Route::delete('/pending/{pending}', [PendingController::class, 'destroy'])->name('pending.destroy');

        // Request ke Gudang (stok gudang tempua)
        Route::resource('transfer-requests', \App\Http\Controllers\Hendhys\TransferRequestController::class)->except(['edit', 'update', 'destroy']);
        Route::get('transfer-requests/{transfer_out}/receive', [\App\Http\Controllers\Master\ReceiptController::class, 'showReceiveForm'])->name('transfer-requests.receive-form-gudang');
        Route::post('transfer-requests/{transfer_out}/receive', [\App\Http\Controllers\Master\ReceiptController::class, 'receive'])->name('transfer-requests.receive-gudang');



        // Request Cabang ke Pusat
        Route::resource('branch-requests', BranchRequestController::class)->except(['edit', 'update', 'destroy']);

        // Distribusi ke Cabang
        Route::resource('transfer-to-branch', TransferToBranchController::class)->except(['edit', 'update', 'destroy']);
        Route::get('transfer-to-branch/{transfer_to_branch}/receive', [TransferToBranchController::class, 'showReceiveForm'])->name('transfer-to-branch.receive-form');
        Route::post('transfer-to-branch/{transfer_to_branch}/receive', [TransferToBranchController::class, 'receive'])->name('transfer-to-branch.receive');

        // Retur dari Cabang
        Route::resource('returns', ReturnController::class)->except(['edit', 'update', 'destroy']);
        Route::post('returns/{return}/receive', [ReturnController::class, 'receive'])->name('returns.receive');

        // Stok Pusat & Cabang
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');

    });
