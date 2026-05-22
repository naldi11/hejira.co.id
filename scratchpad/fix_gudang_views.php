<?php

$indexFile = __DIR__ . '/../resources/views/gudang/stock/index.blade.php';

if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    
    // Remove Jenis filter
    $content = preg_replace('/<select name="jenis".*?<\/select>/s', '', $content);
    
    // Fix clear filter condition
    $content = str_replace("'search', 'jenis', 'low_stock'", "'search', 'low_stock'", $content);
    
    // Update TH
    $content = str_replace('<th class="px-4 py-3 font-medium">Kategori / Jenis</th>', '<th class="px-4 py-3 font-medium">Kategori</th>', $content);
    
    // Update TD
    $content = preg_replace('/<span class="text-xs text-gray-400 capitalize">\{\{ str_replace\(\'_\', \' \', \\\$item->jenis\) \}\}<\/span>/', '', $content);
    
    file_put_contents($indexFile, $content);
    echo "Fixed gudang/stock/index.blade.php\n";
}
