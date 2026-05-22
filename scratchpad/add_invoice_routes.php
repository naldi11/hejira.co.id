<?php

// 1. Add generateInvoice methods to Hendhys and Jihans PosController

// Jihans
$jihansPos = __DIR__ . '/../app/Http/Controllers/Jihans/PosController.php';
$content = file_get_contents($jihansPos);
if (strpos($content, 'public function invoice') === false) {
    // Add InvoiceService to constructor if not exists
    if (strpos($content, 'InvoiceService $invoiceService') === false) {
        $content = str_replace(
            "use App\Services\StockService;",
            "use App\Services\StockService;\nuse App\Services\InvoiceService;",
            $content
        );
        $content = str_replace(
            "private ActivityLogService \$logger",
            "private ActivityLogService \$logger,\n        private InvoiceService \$invoiceService",
            $content
        );
    }
    
    $invoiceMethod = '
    public function invoice($id)
    {
        $transaction = JihansTransaction::findOrFail($id);
        return $this->invoiceService->generateJihansInvoice($transaction);
    }
';
    $content = preg_replace('/}\s*$/', $invoiceMethod . "\n}\n", $content);
    file_put_contents($jihansPos, $content);
    echo "Added invoice to Jihans PosController\n";
}

// Hendhys
$hendhysPos = __DIR__ . '/../app/Http/Controllers/Hendhys/PosController.php';
$content = file_get_contents($hendhysPos);
if (strpos($content, 'public function invoice') === false) {
    if (strpos($content, 'InvoiceService $invoiceService') === false) {
        $content = str_replace(
            "use App\Services\StockService;",
            "use App\Services\StockService;\nuse App\Services\InvoiceService;",
            $content
        );
        $content = str_replace(
            "private ActivityLogService \$logger",
            "private ActivityLogService \$logger,\n        private InvoiceService \$invoiceService",
            $content
        );
    }
    
    $invoiceMethod = '
    public function invoice($id)
    {
        $transaction = HendhysTransaction::findOrFail($id);
        return $this->invoiceService->generateHendhysInvoice($transaction);
    }
';
    $content = preg_replace('/}\s*$/', $invoiceMethod . "\n}\n", $content);
    file_put_contents($hendhysPos, $content);
    echo "Added invoice to Hendhys PosController\n";
}

// 2. Update routes
$jihansRoute = __DIR__ . '/../routes/jihans.php';
$content = file_get_contents($jihansRoute);
if (strpos($content, "Route::get('pos/{transaction}/invoice'") === false) {
    $content = str_replace(
        "Route::post('pos', [\App\Http\Controllers\Jihans\PosController::class, 'store'])->name('pos.store');",
        "Route::post('pos', [\App\Http\Controllers\Jihans\PosController::class, 'store'])->name('pos.store');\n        Route::get('pos/{transaction}/invoice', [\App\Http\Controllers\Jihans\PosController::class, 'invoice'])->name('pos.invoice');",
        $content
    );
    file_put_contents($jihansRoute, $content);
    echo "Added invoice route to Jihans\n";
}

$hendhysRoute = __DIR__ . '/../routes/hendhys.php';
$content = file_get_contents($hendhysRoute);
if (strpos($content, "Route::get('pos/{transaction}/invoice'") === false) {
    $content = str_replace(
        "Route::post('pos', [\App\Http\Controllers\Hendhys\PosController::class, 'store'])->name('pos.store');",
        "Route::post('pos', [\App\Http\Controllers\Hendhys\PosController::class, 'store'])->name('pos.store');\n        Route::get('pos/{transaction}/invoice', [\App\Http\Controllers\Hendhys\PosController::class, 'invoice'])->name('pos.invoice');",
        $content
    );
    file_put_contents($hendhysRoute, $content);
    echo "Added invoice route to Hendhys\n";
}
