<?php

$dir = __DIR__ . '/../database/migrations/';

$masterTables = [
    'master_suppliers' => 'suppliers',
    'master_customers' => 'customers',
    'master_product_categories' => 'product_categories',
    'master_units' => 'units',
    'master_brands' => 'brands',
    'master_products' => 'products',
];

$entities = ['gudang', 'jihans', 'hendhys'];

$files = glob($dir . '*.php');

// Step 1: Duplicate and rename the master migrations
foreach ($files as $file) {
    $basename = basename($file);
    foreach ($masterTables as $master => $suffix) {
        if (strpos($basename, $master) !== false) {
            $content = file_get_contents($file);
            
            foreach ($entities as $entity) {
                // Determine new filename
                // e.g. 2026_05_16_120001_create_master_suppliers_table.php 
                // becomes 2026_05_16_120001_create_gudang_suppliers_table.php
                // wait, timestamps need to be slightly different to avoid conflict? No, we can just use 12010X for gudang, 12020X for jihans, 12030X for hendhys
                
                $newTableName = $entity . '_' . $suffix;
                $newContent = str_replace($master, $newTableName, $content);
                // Also replace other master references inside the table (e.g. products references categories)
                foreach ($masterTables as $m => $s) {
                    $newContent = str_replace($m, $entity . '_' . $s, $newContent);
                }
                
                $prefixTime = '';
                if ($entity == 'gudang') $prefixTime = '12010';
                if ($entity == 'jihans') $prefixTime = '12020';
                if ($entity == 'hendhys') $prefixTime = '12030';
                
                $newBasename = preg_replace('/12000(\d)_create_master_/', $prefixTime . '$1_create_' . $entity . '_', $basename);
                
                file_put_contents($dir . $newBasename, $newContent);
                echo "Created $newBasename\n";
            }
            // Delete original master migration
            unlink($file);
            echo "Deleted original $basename\n";
        }
    }
}

// Step 2: Replace foreign keys in all other transaction migrations
$files = glob($dir . '*.php');
foreach ($files as $file) {
    $basename = basename($file);
    $entityMatch = null;
    if (strpos($basename, 'gudang_') !== false) $entityMatch = 'gudang';
    if (strpos($basename, 'jihans_') !== false) $entityMatch = 'jihans';
    if (strpos($basename, 'hendhys_') !== false) $entityMatch = 'hendhys';
    
    if ($entityMatch) {
        $content = file_get_contents($file);
        $changed = false;
        foreach ($masterTables as $master => $suffix) {
            if (strpos($content, $master) !== false) {
                $content = str_replace($master, $entityMatch . '_' . $suffix, $content);
                $changed = true;
            }
        }
        if ($changed) {
            file_put_contents($file, $content);
            echo "Updated foreign keys in $basename to use $entityMatch\n";
        }
    }
}

echo "Done.\n";
