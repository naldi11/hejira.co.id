<?php

use App\Http\Controllers\Gudang\PurchaseOrderController;
use App\Http\Controllers\Gudang\ReceivingController;
use App\Http\Controllers\Gudang\StockController;
use App\Http\Controllers\Gudang\TransferOutController;
use App\Http\Controllers\Gudang\TransferRequestController;
use App\Http\Controllers\Master\BranchController;
use App\Http\Controllers\Master\BrandController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ProductCategoryController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\UnitController;
use Illuminate\Support\Facades\Route;

// ── Gudang Operasional ───────────────────────────────────────────────────────
Route::middleware(['auth', 'check.entity:gudang', 'role:admin_gudang'])
    ->prefix('gudang')
    ->name('gudang.')
    ->group(function () {

        Route::get('/dashboard', [App\Http\Controllers\Gudang\DashboardController::class, 'index'])->name('dashboard');

        // Purchase Order
        Route::resource('po', PurchaseOrderController::class)->except(['destroy']);
        Route::post('po/{po}/send',   [PurchaseOrderController::class, 'send'])->name('po.send');
        Route::post('po/{po}/cancel', [PurchaseOrderController::class, 'cancel'])->name('po.cancel');
        Route::get('po/{po}/print',   [PurchaseOrderController::class, 'print'])->name('po.print');

        // Penerimaan Barang (GRN)
        Route::get('receiving',                                           [ReceivingController::class, 'index'])->name('receiving.index');
        Route::get('receiving/create',                                    [ReceivingController::class, 'create'])->name('receiving.create');
        Route::post('receiving',                                          [ReceivingController::class, 'store'])->name('receiving.store');
        Route::get('receiving/{receiving}',                               [ReceivingController::class, 'show'])->name('receiving.show');
        Route::put('receiving/{receiving}',                               [ReceivingController::class, 'update'])->name('receiving.update');
        Route::post('receiving/{receiving}/close',                        [ReceivingController::class, 'close'])->name('receiving.close');
        Route::get('receiving/{receiving}/print',                         [ReceivingController::class, 'print'])->name('receiving.print');
        Route::post('receiving/{receiving}/photos',                       [ReceivingController::class, 'uploadPhoto'])->name('receiving.photos.store');
        Route::delete('receiving/{receiving}/photos/{photo}',             [ReceivingController::class, 'deletePhoto'])->name('receiving.photos.destroy');

        // Stok Gudang
        Route::get('stock',           [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/movements', [StockController::class, 'movements'])->name('stock.movements');
        Route::post('stock/adjust',   [StockController::class, 'adjust'])->name('stock.adjust');

        // Transfer Request (Approval)
        Route::get('transfer-requests',                              [TransferRequestController::class, 'index'])->name('transfer-requests.index');
        Route::get('transfer-requests/{transferRequest}',            [TransferRequestController::class, 'show'])->name('transfer-requests.show');
        Route::post('transfer-requests/{transferRequest}/approve',   [TransferRequestController::class, 'approve'])->name('transfer-requests.approve');
        Route::post('transfer-requests/{transferRequest}/reject',    [TransferRequestController::class, 'reject'])->name('transfer-requests.reject');

        // Transfer Keluar
        Route::get('transfer-out',               [TransferOutController::class, 'index'])->name('transfer-out.index');
        Route::get('transfer-out/create',        [TransferOutController::class, 'create'])->name('transfer-out.create');
        Route::post('transfer-out',              [TransferOutController::class, 'store'])->name('transfer-out.store');
        Route::get('transfer-out/{transferOut}', [TransferOutController::class, 'show'])->name('transfer-out.show');

    });

// ── Master Data (dikelola Admin Gudang) ──────────────────────────────────────
Route::middleware(['auth', 'role:admin_gudang'])
    ->prefix('master')
    ->name('master.')
    ->group(function () {

        Route::resource('suppliers', SupplierController::class)->except(['show']);
        Route::resource('customers', CustomerController::class)->except(['show']);
        Route::get('products/template', [ProductController::class, 'downloadTemplate'])->name('products.template');
        Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
        Route::resource('products',  ProductController::class)->except(['show']);
        Route::resource('branches',  BranchController::class)->except(['show']);
        Route::resource('users',     App\Http\Controllers\Master\UserController::class)->except(['show']);

        Route::get('categories',               [ProductCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories',              [ProductCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}',    [ProductCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [ProductCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('units',           [UnitController::class, 'index'])->name('units.index');
        Route::post('units',          [UnitController::class, 'store'])->name('units.store');
        Route::put('units/{unit}',    [UnitController::class, 'update'])->name('units.update');
        Route::delete('units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

        Route::get('brands',            [BrandController::class, 'index'])->name('brands.index');
        Route::post('brands',           [BrandController::class, 'store'])->name('brands.store');
        Route::put('brands/{brand}',    [BrandController::class, 'update'])->name('brands.update');
        Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

    });
