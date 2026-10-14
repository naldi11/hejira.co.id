<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Izzi\{OperationsController, MasterController};

Route::middleware(['auth', 'check.branch', 'role:owner|super_admin|admin_izzi|kasir_izzi'])->prefix('izzi')->name('izzi.')->group(function () {
    Route::get('/', [OperationsController::class, 'dashboard'])->name('dashboard');
    Route::get('/stock', [OperationsController::class, 'stock'])->name('stock');
    Route::get('/stock/movements', [OperationsController::class, 'movements'])->name('movements');
    Route::get('/sales', [OperationsController::class, 'sales'])->name('sales');
    Route::get('/sales/{sale}', [OperationsController::class, 'show'])->whereNumber('sale')->name('sales.show');
    Route::get('/sales/{sale}/receipt', [OperationsController::class, 'show'])->whereNumber('sale')->name('sales.receipt');
    Route::get('/receipts', [OperationsController::class, 'receipts'])->name('receipts');
    Route::get('/shifts', [OperationsController::class, 'shifts'])->name('shifts');
    Route::middleware('role:kasir_izzi')->group(function () {
        Route::get('/pos', [OperationsController::class, 'pos'])->name('pos');
        Route::post('/pos', [OperationsController::class, 'checkout'])->name('checkout');
        Route::post('/shifts/open', [OperationsController::class, 'openShift'])->name('shifts.open');
        Route::post('/shifts/close', [OperationsController::class, 'closeShift'])->name('shifts.close');
    });
    Route::middleware('role:admin_izzi|super_admin')->group(function () {
        Route::get('/ingredients', [MasterController::class, 'ingredients'])->name('ingredients');
        Route::post('/ingredients', [MasterController::class, 'saveIngredient'])->name('ingredients.store');
        Route::put('/ingredients/{product}', [MasterController::class, 'saveIngredient'])->whereNumber('product')->name('ingredients.update');
        Route::get('/menus', [MasterController::class, 'menus'])->name('menus');
        Route::get('/menus/create', [MasterController::class, 'menuForm'])->name('menus.create');
        Route::post('/menus', [MasterController::class, 'saveMenu'])->name('menus.store');
        Route::get('/menus/{menu}/edit', [MasterController::class, 'menuForm'])->whereNumber('menu')->name('menus.edit');
        Route::put('/menus/{menu}', [MasterController::class, 'saveMenu'])->whereNumber('menu')->name('menus.update');
        Route::get('/receipts/create', [OperationsController::class, 'receiveForm'])->name('receipts.create');
        Route::post('/receipts', [OperationsController::class, 'receive'])->name('receipts.store');
        Route::post('/sales/{sale}/cancel', [OperationsController::class, 'cancel'])->whereNumber('sale')->name('sales.cancel');
    });
});
