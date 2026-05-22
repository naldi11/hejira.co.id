<?php

$dir = __DIR__ . '/../app/Http/Controllers/Gudang/';

// 1. Fix PurchaseOrderController
$poFile = $dir . 'PurchaseOrderController.php';
if (file_exists($poFile)) {
    $content = file_get_contents($poFile);
    // Add entity_scope filter to products
    $content = preg_replace(
        "/Product::where\('status', 'active'\)->with\('unit'\)->orderBy\('name'\)->get\(\);/",
        "Product::where('status', 'active')->whereIn('entity_scope', ['gudang', 'all'])->with('unit')->orderBy('name')->get();",
        $content
    );
    file_put_contents($poFile, $content);
    echo "Fixed PurchaseOrderController\n";
}

// 2. Fix ReceivingController
$rcvFile = $dir . 'ReceivingController.php';
if (file_exists($rcvFile)) {
    $content = file_get_contents($rcvFile);
    // Add entity_scope filter to products
    $content = preg_replace(
        "/Product::where\('status', 'active'\)->with\('unit'\)->orderBy\('name'\)->get\(\);/",
        "Product::where('status', 'active')->whereIn('entity_scope', ['gudang', 'all'])->with('unit')->orderBy('name')->get();",
        $content
    );
    file_put_contents($rcvFile, $content);
    echo "Fixed ReceivingController\n";
}

// 3. Fix StockController
$stockFile = $dir . 'StockController.php';
if (file_exists($stockFile)) {
    $content = file_get_contents($stockFile);
    // Add entity_scope filter to products
    if (strpos($content, "->whereIn('master_products.entity_scope', ['gudang', 'all'])") === false) {
        $content = str_replace(
            "->where('master_products.status', 'active')",
            "->where('master_products.status', 'active')\n            ->whereIn('master_products.entity_scope', ['gudang', 'all'])",
            $content
        );
    }
    // Remove jenis filter since we removed it from database
    $content = preg_replace("/if \(\\\$request->filled\('jenis'\)\) \\\$q->where\('master_products.jenis', \\\$request->jenis\);\s*/", "", $content);
    file_put_contents($stockFile, $content);
    echo "Fixed StockController\n";
}

// 4. Fix TransferOutController
$trfFile = $dir . 'TransferOutController.php';
if (file_exists($trfFile)) {
    $content = file_get_contents($trfFile);
    // Add entity_scope filter to products
    if (strpos($content, "->whereIn('master_products.entity_scope', ['gudang', 'all'])") === false) {
        $content = str_replace(
            "Product::where('status', 'active')",
            "Product::where('status', 'active')->whereIn('master_products.entity_scope', ['gudang', 'all'])",
            $content
        );
    }
    file_put_contents($trfFile, $content);
    echo "Fixed TransferOutController\n";
}

// 5. Fix Hendhys TransferRequestController
$hendhysTrqFile = __DIR__ . '/../app/Http/Controllers/Hendhys/TransferRequestController.php';
if (file_exists($hendhysTrqFile)) {
    $content = file_get_contents($hendhysTrqFile);
    // Change GudangProduct to Product
    $content = str_replace('use App\Models\Gudang\Product as GudangProduct;', 'use App\Models\Product;', $content);
    $content = str_replace(
        "GudangProduct::where('status', 'active')->orderBy('name')->get();",
        "Product::where('status', 'active')->whereIn('entity_scope', ['gudang', 'all'])->orderBy('name')->get();",
        $content
    );
    file_put_contents($hendhysTrqFile, $content);
    echo "Fixed Hendhys TransferRequestController\n";
}

// 6. Fix Jihans TransferRequestController
$jihansTrqFile = __DIR__ . '/../app/Http/Controllers/Jihans/TransferRequestController.php';
if (file_exists($jihansTrqFile)) {
    $content = file_get_contents($jihansTrqFile);
    // Change GudangProduct to Product
    $content = str_replace('use App\Models\Gudang\Product as GudangProduct;', 'use App\Models\Product;', $content);
    $content = str_replace(
        "GudangProduct::where('status', 'active')->with('unit')->orderBy('name')->get();",
        "Product::where('status', 'active')->whereIn('entity_scope', ['gudang', 'all'])->with('unit')->orderBy('name')->get();",
        $content
    );
    file_put_contents($jihansTrqFile, $content);
    echo "Fixed Jihans TransferRequestController\n";
}
