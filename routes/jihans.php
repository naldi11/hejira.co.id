<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Jihans\ProductionController;
use App\Http\Controllers\Jihans\PosController;
use App\Http\Controllers\Jihans\PendingController;
use App\Http\Controllers\Jihans\TransferRequestController;
use App\Http\Controllers\Jihans\StockController;
use App\Http\Controllers\Jihans\GudangReturnController;

Route::middleware(['auth', 'check.entity:jihans', 'role:kasir_jihans|admin_jihans'])
    ->prefix('jihans')
    ->name('jihans.')
    ->group(function () {

        Route::get('/dashboard', [\App\Http\Controllers\Jihans\DashboardController::class, 'index'])->name('dashboard');

        // Master Data (Scoped to Jihans)
        Route::prefix('master')->name('master.')->group(function () {
            Route::resource('suppliers', \App\Http\Controllers\Master\SupplierController::class)->except(['show']);
            Route::get('customers/template', [\App\Http\Controllers\Master\CustomerController::class, 'downloadTemplate'])->name('customers.template');
            Route::post('customers/import', [\App\Http\Controllers\Master\CustomerController::class, 'import'])->name('customers.import');
            Route::resource('customers', \App\Http\Controllers\Master\CustomerController::class)->except(['show']);
            Route::get('products/template', [\App\Http\Controllers\Master\ProductController::class, 'downloadTemplate'])->name('products.template');
            Route::post('products/import', [\App\Http\Controllers\Master\ProductController::class, 'import'])->name('products.import');
            Route::resource('products',  \App\Http\Controllers\Master\ProductController::class)->except(['show']);
            

            Route::resource('karyawan', \App\Http\Controllers\Master\KaryawanController::class)->except(['show']);

            Route::get('production-config', [\App\Http\Controllers\Jihans\JihansProductionConfigController::class, 'edit'])->name('production-config.edit');
            Route::put('production-config', [\App\Http\Controllers\Jihans\JihansProductionConfigController::class, 'update'])->name('production-config.update');
        });

        // Produksi Tortilla (Opsi A)
        Route::get('tortilla/recap', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'recap'])->name('tortilla.recap');
        Route::get('tortilla/recap/export', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'exportRecap'])->name('tortilla.recap.export');
        Route::get('tortilla/prediksi/create', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'createPrediksi'])->name('tortilla.prediksi.create');
        Route::post('tortilla/prediksi', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'storePrediksi'])->name('tortilla.prediksi.store');
        Route::get('tortilla/{tortilla}/faktur', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'printFaktur'])->name('tortilla.faktur');
        Route::resource('tortilla', \App\Http\Controllers\Jihans\TortillaProductionController::class)->except(['edit', 'update', 'destroy']);

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
        Route::get('transfer-requests/{transfer_out}/receive', [\App\Http\Controllers\Master\ReceiptController::class, 'showReceiveForm'])->name('transfer-requests.receive-form');
        Route::post('transfer-requests/{transfer_out}/receive', [\App\Http\Controllers\Master\ReceiptController::class, 'receive'])->name('transfer-requests.receive');
        Route::get('transfer-requests/{transfer_out}/bast', [\App\Http\Controllers\Master\ReceiptController::class, 'print'])->name('transfer-requests.print');

        // Retur ke Gudang
        Route::resource('returns-to-gudang', GudangReturnController::class)->only(['index', 'create', 'store', 'show']);

        // Stok
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');

        // Laporan
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/',           [\App\Http\Controllers\Jihans\ReportController::class, 'index'])->name('index');
            Route::get('/laci',       [\App\Http\Controllers\Jihans\ReportController::class, 'laci'])->name('laci');
            Route::get('/harian',     [\App\Http\Controllers\Jihans\ReportController::class, 'harian'])->name('harian');
            Route::get('/mingguan',   [\App\Http\Controllers\Jihans\ReportController::class, 'mingguan'])->name('mingguan');
            Route::get('/bulanan',    [\App\Http\Controllers\Jihans\ReportController::class, 'bulanan'])->name('bulanan');
            Route::get('/pelanggan',  [\App\Http\Controllers\Jihans\ReportController::class, 'pelanggan'])->name('pelanggan');
            Route::get('/pdf/{type}', [\App\Http\Controllers\Jihans\ReportController::class, 'pdf'])->name('pdf');
        });

    });
