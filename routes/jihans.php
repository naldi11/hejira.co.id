<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Jihans\ProductionController;
use App\Http\Controllers\Jihans\PosController;
use App\Http\Controllers\Jihans\PendingController;
use App\Http\Controllers\Jihans\TransferRequestController;
use App\Http\Controllers\Jihans\StockController;
use App\Http\Controllers\Jihans\GudangReturnController;

Route::middleware(['auth', 'check.entity:jihans', 'role:kasir_jihans|admin_jihans|super_admin_jihans'])
    ->prefix('jihans')
    ->name('jihans.')
    ->group(function () {

        Route::get('/dashboard', [\App\Http\Controllers\Jihans\DashboardController::class, 'index'])->name('dashboard');

        // Shared Routes: Riwayat Transaksi & Stock View
        Route::resource('transactions', \App\Http\Controllers\Jihans\TransactionController::class)->only(['index', 'show']);
        Route::get('transactions/{transaction}/pdf', [\App\Http\Controllers\Jihans\TransactionController::class, 'pdf'])->name('transactions.pdf');
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');

        // Laci Report (Accessible by both Kasir and Admin)
        Route::get('/reports/laci', [\App\Http\Controllers\Jihans\ReportController::class, 'laci'])
            ->middleware('role:kasir_jihans|admin_jihans|super_admin_jihans')
            ->name('reports.laci');

        // Shift Control Routes
        Route::middleware('role:kasir_jihans|admin_jihans|super_admin_jihans')->group(function () {
            Route::post('/shifts/open', [\App\Http\Controllers\Shared\ShiftController::class, 'open'])->name('shifts.open');
            Route::post('/shifts/close', [\App\Http\Controllers\Shared\ShiftController::class, 'close'])->name('shifts.close');
            Route::get('/shifts/status', [\App\Http\Controllers\Shared\ShiftController::class, 'status'])->name('shifts.status');
            Route::get('/shifts/{shift}/details', [\App\Http\Controllers\Shared\ShiftController::class, 'show'])->name('shifts.details');
            
            // PDF Export route (accessible by Kasir & Admin)
            Route::get('/reports/pdf/{type}', [\App\Http\Controllers\Jihans\ReportController::class, 'pdf'])->name('reports.pdf');
        });

        // ==========================================
        // KASIR ONLY ROUTES
        // ==========================================
        Route::middleware(['role:kasir_jihans|super_admin_jihans'])->group(function () {
            // POS routes gated by active shift
            Route::middleware('check.active.shift')->group(function () {
                // POS Kasir
                Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
                Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
                Route::get('/pos/{transaction}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');

                // Transaksi Pending
                Route::get('/pending', [PendingController::class, 'index'])->name('pending.index');
                Route::post('/pending', [PendingController::class, 'store'])->name('pending.store');
                Route::get('/pending/{pending}', [PendingController::class, 'show'])->name('pending.show');
                Route::delete('/pending/{pending}', [PendingController::class, 'destroy'])->name('pending.destroy');
            });
        });

        // ==========================================
        // ADMIN ONLY ROUTES
        // ==========================================
        Route::middleware(['role:admin_jihans|super_admin_jihans'])->group(function () {
            // Master Data (Scoped to Jihans)
            Route::prefix('master')->name('master.')->group(function () {
                Route::get('suppliers/template', [\App\Http\Controllers\Master\SupplierController::class, 'downloadTemplate'])->name('suppliers.template');
                Route::post('suppliers/import', [\App\Http\Controllers\Master\SupplierController::class, 'import'])->name('suppliers.import');
                Route::resource('suppliers', \App\Http\Controllers\Master\SupplierController::class)->except(['show']);
                Route::get('customers/template', [\App\Http\Controllers\Master\CustomerController::class, 'downloadTemplate'])->name('customers.template');
                Route::post('customers/import', [\App\Http\Controllers\Master\CustomerController::class, 'import'])->name('customers.import');
                Route::resource('customers', \App\Http\Controllers\Master\CustomerController::class)->except(['show']);
                Route::get('products/template', [\App\Http\Controllers\Master\ProductController::class, 'downloadTemplate'])->name('products.template');
                Route::post('products/import', [\App\Http\Controllers\Master\ProductController::class, 'import'])->name('products.import');
                Route::resource('products',  \App\Http\Controllers\Master\ProductController::class)->except(['show']);
                
                Route::resource('karyawan', \App\Http\Controllers\Master\KaryawanController::class)->except(['show']);


            });

            // Produksi (Dinamis)
            Route::get('production/recap', [\App\Http\Controllers\Jihans\ProductionController::class, 'recap'])->name('production.recap');
            Route::get('production/recap/export', [\App\Http\Controllers\Jihans\ProductionController::class, 'exportRecap'])->name('production.recap.export');
            Route::get('production/prediksi/create', [\App\Http\Controllers\Jihans\ProductionController::class, 'createPrediksi'])->name('production.prediksi.create');
            Route::post('production/prediksi', [\App\Http\Controllers\Jihans\ProductionController::class, 'storePrediksi'])->name('production.prediksi.store');
            Route::get('production/{production}/prediksi/edit', [\App\Http\Controllers\Jihans\ProductionController::class, 'editPrediksi'])->name('production.prediksi.edit');
            Route::put('production/{production}/prediksi', [\App\Http\Controllers\Jihans\ProductionController::class, 'updatePrediksi'])->name('production.prediksi.update');
            Route::delete('production/{production}/prediksi', [\App\Http\Controllers\Jihans\ProductionController::class, 'destroyPrediksi'])->name('production.prediksi.destroy');
            Route::get('production/{production}/faktur', [\App\Http\Controllers\Jihans\ProductionController::class, 'printFaktur'])->name('production.faktur');
            Route::resource('production', \App\Http\Controllers\Jihans\ProductionController::class)->except(['edit', 'update', 'destroy']);



            // Laporan Bisnis (Minus PDF route which is now shared)
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/',           [\App\Http\Controllers\Jihans\ReportController::class, 'index'])->name('index');
                Route::get('/harian',     [\App\Http\Controllers\Jihans\ReportController::class, 'harian'])->name('harian');
                Route::get('/mingguan',   [\App\Http\Controllers\Jihans\ReportController::class, 'mingguan'])->name('mingguan');
                Route::get('/bulanan',    [\App\Http\Controllers\Jihans\ReportController::class, 'bulanan'])->name('bulanan');
                Route::get('/pelanggan',  [\App\Http\Controllers\Jihans\ReportController::class, 'pelanggan'])->name('pelanggan');
            });
        });

        // Shared: Request ke Gudang & Retur ke Gudang (Kasir & Admin)
        Route::resource('transfer-requests', TransferRequestController::class)->except(['edit', 'update', 'destroy', 'show']);
        Route::get('transfer-requests/{transfer_out}/receive', [\App\Http\Controllers\Master\ReceiptController::class, 'showReceiveForm'])->name('transfer-requests.receive-form');
        Route::post('transfer-requests/{transfer_out}/receive', [\App\Http\Controllers\Master\ReceiptController::class, 'receive'])->name('transfer-requests.receive');
        Route::get('transfer-requests/{transfer_out}/bast', [\App\Http\Controllers\Master\ReceiptController::class, 'print'])->name('transfer-requests.print');
        Route::get('transfer-requests/{transferRequest}', [TransferRequestController::class, 'show'])->name('transfer-requests.show');

        Route::resource('returns-to-gudang', GudangReturnController::class)->only(['index', 'create', 'store', 'show']);

    });

