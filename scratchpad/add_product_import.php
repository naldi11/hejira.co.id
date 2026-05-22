<?php

$controllerPath = __DIR__ . '/../app/Http/Controllers/Master/ProductController.php';

if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    
    // Check if import method already exists
    if (strpos($content, 'public function import') === false) {
        $importMethods = '
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\Master\ProductsTemplateExport, "Produk_Template.xlsx");
    }

    public function import(Request $request)
    {
        $request->validate([
            \'file\' => \'required|mimes:xlsx,xls,csv\'
        ]);

        $info = $this->getScopeInfo($request);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\Master\ProductsImport, $request->file(\'file\'));
            $this->logger->log(\'import\', \'master.product\', "Import produk via Excel");
            return redirect()->route($info[\'route\'] . \'products.index\')->with(\'success\', \'Produk berhasil di-import dan diperbarui dari file Excel.\');
        } catch (\Exception $e) {
            return redirect()->back()->with(\'error\', \'Terjadi kesalahan saat import: \' . $e->getMessage());
        }
    }
';
        // Insert right before the last closing brace
        $content = preg_replace('/}\s*$/', $importMethods . "\n}\n", $content);
        file_put_contents($controllerPath, $content);
        echo "Added import/export methods to ProductController\n";
    } else {
        echo "import method already exists.\n";
    }
}

// 2. Add Routes
$routeFiles = [
    __DIR__ . '/../routes/gudang.php',
    __DIR__ . '/../routes/jihans.php',
    __DIR__ . '/../routes/hendhys.php'
];

foreach ($routeFiles as $rf) {
    if (file_exists($rf)) {
        $content = file_get_contents($rf);
        if (strpos($content, "Route::get('products/template'") === false) {
            // Find Route::resource('products' and put these above it.
            $search = "Route::resource('products'";
            $replace = "Route::get('products/template', [\App\Http\Controllers\Master\ProductController::class, 'downloadTemplate'])->name('products.template');\n            Route::post('products/import', [\App\Http\Controllers\Master\ProductController::class, 'import'])->name('products.import');\n            Route::resource('products'";
            
            // Note: gudang.php might just use ProductController::class without full namespace
            if (basename($rf) === 'gudang.php') {
                $replace = "Route::get('products/template', [ProductController::class, 'downloadTemplate'])->name('products.template');\n        Route::post('products/import', [ProductController::class, 'import'])->name('products.import');\n        Route::resource('products'";
            }
            
            $content = str_replace($search, $replace, $content);
            file_put_contents($rf, $content);
            echo "Added routes to " . basename($rf) . "\n";
        }
    }
}
