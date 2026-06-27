<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hendhys\ProductionController;
use App\Http\Controllers\Hendhys\PosController;
use App\Http\Controllers\Hendhys\PendingController;

use App\Http\Controllers\Hendhys\BranchRequestController;
use App\Http\Controllers\Hendhys\TransferToBranchController;
use App\Http\Controllers\Hendhys\ReturnController;
use App\Http\Controllers\Hendhys\StockController;
use App\Http\Controllers\Hendhys\GudangReturnController;

use App\Http\Controllers\Hendhys\DashboardController;

Route::middleware(['auth', 'check.entity:hendhys', 'check.branch', 'role:kasir_hendhys|admin_hendhys|super_admin_hendhys'])
    ->prefix('hendhys')
    ->name('hendhys.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Shared Routes: Riwayat Transaksi & Stock View
        Route::resource('transactions', \App\Http\Controllers\Hendhys\TransactionController::class)->only(['index', 'show']);
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');

        // Laci Report (Accessible by both Kasir and Admin)
        Route::get('/reports/laci', [\App\Http\Controllers\Hendhys\ReportController::class, 'laci'])
            ->middleware('role:kasir_hendhys|admin_hendhys|super_admin_hendhys')
            ->name('reports.laci');

        // ==========================================
        // KASIR ONLY ROUTES
        // ==========================================
        Route::middleware(['role:kasir_hendhys|super_admin_hendhys'])->group(function () {
            // POS (pusat & cabang)
            Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
            Route::get('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
            Route::get('/pos/held-stock', [PosController::class, 'heldStock'])->name('pos.held-stock');
            Route::get('/pos/customer-search', [PosController::class, 'customerSearch'])->name('pos.customer-search');
            Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
            Route::get('/pos/{transaction}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');
            Route::get('/pos/{transaction}/invoice', [PosController::class, 'invoice'])->name('pos.invoice');

            // Transaksi Pending
            Route::get('/pending', [PendingController::class, 'index'])->name('pending.index');
            Route::post('/pending', [PendingController::class, 'store'])->name('pending.store');
            Route::get('/pending/{pending}', [PendingController::class, 'show'])->name('pending.show');
            Route::delete('/pending/{pending}', [PendingController::class, 'destroy'])->name('pending.destroy');

            // Receive from Pusat (Cabang receiving)
            Route::get('transfer-to-branch/{transfer_to_branch}/receive', [TransferToBranchController::class, 'showReceiveForm'])->name('transfer-to-branch.receive-form');
            Route::post('transfer-to-branch/{transfer_to_branch}/receive', [TransferToBranchController::class, 'receive'])->name('transfer-to-branch.receive');
        });

        // ==========================================
        // ADMIN ONLY ROUTES
        // ==========================================
        Route::middleware(['role:admin_hendhys|super_admin_hendhys'])->group(function () {
            // Master Data (Scoped to Hendhys)
            Route::prefix('master')->name('master.')->group(function () {
                Route::get('suppliers/template', [\App\Http\Controllers\Master\SupplierController::class, 'downloadTemplate'])->name('suppliers.template');
                Route::post('suppliers/import', [\App\Http\Controllers\Master\SupplierController::class, 'import'])->name('suppliers.import');
                Route::resource('suppliers', \App\Http\Controllers\Master\SupplierController::class)->except(['show']);
                Route::get('customers/template', [\App\Http\Controllers\Master\CustomerController::class, 'downloadTemplate'])->name('customers.template');
                Route::post('customers/import', [\App\Http\Controllers\Master\CustomerController::class, 'import'])->name('customers.import');
                Route::resource('customers', \App\Http\Controllers\Master\CustomerController::class)->except(['show']);
                Route::get('products/template', [\App\Http\Controllers\Master\ProductController::class, 'downloadTemplate'])->name('products.template');
                Route::post('products/import', [\App\Http\Controllers\Master\ProductController::class, 'import'])->name('products.import');
                Route::resource('products', \App\Http\Controllers\Master\ProductController::class)->except(['show']);
            });


            // Produksi (khusus pusat)
            Route::resource('productions', ProductionController::class)->except(['edit', 'update', 'destroy']);

            // Request ke Gudang
            Route::resource('transfer-requests', \App\Http\Controllers\Hendhys\TransferRequestController::class)->except(['edit', 'update', 'destroy']);
            Route::get('transfer-requests/{transfer_out}/receive', [\App\Http\Controllers\Master\ReceiptController::class, 'showReceiveForm'])->name('transfer-requests.receive-form-gudang');
            Route::post('transfer-requests/{transfer_out}/receive', [\App\Http\Controllers\Master\ReceiptController::class, 'receive'])->name('transfer-requests.receive-gudang');
            Route::get('transfer-requests/{transfer_out}/bast', [\App\Http\Controllers\Master\ReceiptController::class, 'print'])->name('transfer-requests.print-gudang');

            // Request Cabang ke Pusat
            Route::resource('branch-requests', BranchRequestController::class)->except(['edit', 'update', 'destroy']);

            // Distribusi ke Cabang
            Route::post('transfer-to-branch/{transfer_to_branch}/force-receive', [TransferToBranchController::class, 'forceReceive'])->name('transfer-to-branch.force-receive');

            // Retur dari Cabang
            Route::resource('returns', ReturnController::class)->except(['edit', 'update', 'destroy']);
            Route::post('returns/{return}/receive', [ReturnController::class, 'receive'])->name('returns.receive');

            // Retur ke Gudang
            Route::resource('returns-to-gudang', GudangReturnController::class)->only(['index', 'create', 'store', 'show']);

            // Laporan Bisnis
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/',          [\App\Http\Controllers\Hendhys\ReportController::class, 'index'])->name('index');
                Route::get('/harian',    [\App\Http\Controllers\Hendhys\ReportController::class, 'harian'])->name('harian');
                Route::get('/mingguan',  [\App\Http\Controllers\Hendhys\ReportController::class, 'mingguan'])->name('mingguan');
                Route::get('/bulanan',   [\App\Http\Controllers\Hendhys\ReportController::class, 'bulanan'])->name('bulanan');
                Route::get('/pelanggan', [\App\Http\Controllers\Hendhys\ReportController::class, 'pelanggan'])->name('pelanggan');
                Route::get('/pdf/{type}', [\App\Http\Controllers\Hendhys\ReportController::class, 'pdf'])->name('pdf');
            });
        });

        // BAST Print & show & index of transfer-to-branch is shared
        Route::resource('transfer-to-branch', TransferToBranchController::class)->except(['edit', 'update', 'destroy', 'show']);
        Route::get('transfer-to-branch/{transfer_to_branch}', [TransferToBranchController::class, 'show'])->name('transfer-to-branch.show');
        Route::get('transfer-to-branch/{transfer_to_branch}/bast', [TransferToBranchController::class, 'printBast'])->name('transfer-to-branch.bast');

    });

