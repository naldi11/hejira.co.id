<?php

use Illuminate\Support\Facades\Route;

// Root: redirect based on auth state
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Generic /dashboard — redirect to role-appropriate dashboard
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->hasRole('owner'))                          return redirect()->route('owner.dashboard');
    if ($user->hasRole('super_admin'))                   return redirect()->route('gudang.dashboard');
    if ($user->hasRole(['kasir_jihans', 'admin_jihans'])) return redirect()->route('jihans.dashboard');
    if ($user->hasRole(['kasir_hendhys', 'admin_hendhys'])) return redirect()->route('hendhys.dashboard');
    
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login')->withErrors(['email' => 'Akun Anda tidak memiliki role yang valid. Silakan hubungi administrator.']);
})->middleware('auth')->name('dashboard');


Route::middleware('auth')->group(function() {
    Route::middleware('role:super_admin|admin_hendhys|kasir_hendhys|admin_jihans|kasir_jihans')->group(function () {
        Route::get('/branch-transfers', [\App\Http\Controllers\BranchStockTransferController::class, 'index'])->name('branch-transfers.index');
        Route::get('/branch-transfers/history', [\App\Http\Controllers\BranchStockTransferController::class, 'index'])->name('branch-transfers.history');
        Route::get('/branch-transfers/create', [\App\Http\Controllers\BranchStockTransferController::class, 'create'])->name('branch-transfers.create');
        Route::post('/branch-transfers', [\App\Http\Controllers\BranchStockTransferController::class, 'store'])->name('branch-transfers.store');
        Route::get('/branch-transfers/{transfer}', [\App\Http\Controllers\BranchStockTransferController::class, 'show'])->whereNumber('transfer')->name('branch-transfers.show');
        Route::post('/branch-transfers/{transfer}/receive', [\App\Http\Controllers\BranchStockTransferController::class, 'receive'])->whereNumber('transfer')->name('branch-transfers.receive');
        Route::post('/branch-transfers/{transfer}/cancel', [\App\Http\Controllers\BranchStockTransferController::class, 'cancel'])->whereNumber('transfer')->name('branch-transfers.cancel');
    });
    Route::middleware('role:owner|super_admin|admin_hendhys|kasir_hendhys|admin_jihans|kasir_jihans')->group(function () {
        Route::get('/stock-opname', [\App\Http\Controllers\StockOpnameController::class, 'index'])->name('stock-opname.index');
        Route::get('/stock-opname/history', [\App\Http\Controllers\StockOpnameController::class, 'history'])->name('stock-opname.history');
        Route::get('/stock-opname/results/{count}', [\App\Http\Controllers\StockOpnameController::class, 'result'])->whereNumber('count')->name('stock-opname.result');
        Route::get('/stock-opname/{session}', [\App\Http\Controllers\StockOpnameController::class, 'show'])->whereNumber('session')->name('stock-opname.show');
        Route::post('/stock-opname/{session}/counts', [\App\Http\Controllers\StockOpnameController::class, 'count'])->whereNumber('session')->name('stock-opname.count');
        Route::post('/owner/stock-opname', [\App\Http\Controllers\StockOpnameController::class, 'open'])->middleware('role:owner')->name('owner.stock-opname.open');
        Route::post('/owner/stock-opname/{session}/close', [\App\Http\Controllers\StockOpnameController::class, 'close'])->middleware('role:owner')->whereNumber('session')->name('owner.stock-opname.close');
    });
    Route::get('/api/notifications/counts', [\App\Http\Controllers\Api\NotificationController::class, 'getCounts'])->name('api.notifications.counts');
    Route::get('/select-branch', [\App\Http\Controllers\Auth\BranchSelectionController::class, 'show'])->name('select-branch');
    Route::post('/select-branch', [\App\Http\Controllers\Auth\BranchSelectionController::class, 'select'])->name('select-branch.post');

    // Shared QR Label Printing
    Route::get('/qr/products', [\App\Http\Controllers\Master\ProductQrController::class, 'index'])->name('products.qr');
});

require __DIR__.'/auth.php';

Route::get('/flush-opcache', function() {
    if (!auth()->check() || !auth()->user()->hasRole('owner')) {
        abort(403, 'Unauthorized action.');
    }
    if (function_exists('opcache_reset')) {
        opcache_reset();
        return 'OK';
    }
    return 'NO';
});
