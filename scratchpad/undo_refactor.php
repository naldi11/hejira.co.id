<?php

$dir = __DIR__ . '/../database/migrations/';

$masterTables = [
    'suppliers',
    'customers',
    'product_categories',
    'units',
    'brands',
    'products',
];

$entities = ['gudang', 'jihans', 'hendhys'];

// We will keep the 'gudang_' version, rename it to 'master_', and then delete 'jihans_' and 'hendhys_'.
$files = glob($dir . '*.php');

foreach ($masterTables as $tableSuffix) {
    $masterName = 'master_' . $tableSuffix;
    
    // Find the gudang version to rename
    $gudangFile = null;
    foreach ($files as $file) {
        if (strpos(basename($file), 'create_gudang_' . $tableSuffix . '_table') !== false) {
            $gudangFile = $file;
            break;
        }
    }
    
    if ($gudangFile) {
        $content = file_get_contents($gudangFile);
        $content = str_replace("gudang_{$tableSuffix}", $masterName, $content);
        
        // Also replace foreign keys within it (e.g. products referencing categories)
        foreach ($masterTables as $t) {
            $content = str_replace("gudang_{$t}", "master_{$t}", $content);
        }
        
        $newBasename = str_replace('create_gudang_', 'create_master_', basename($gudangFile));
        file_put_contents($dir . $newBasename, $content);
        echo "Created $newBasename\n";
        unlink($gudangFile);
    }
    
    // Delete jihans and hendhys versions
    foreach ($files as $file) {
        if (strpos(basename($file), 'create_jihans_' . $tableSuffix . '_table') !== false || 
            strpos(basename($file), 'create_hendhys_' . $tableSuffix . '_table') !== false) {
            unlink($file);
            echo "Deleted " . basename($file) . "\n";
        }
    }
}

// Step 2: Update all remaining migrations to point to master_ instead of gudang_, jihans_, hendhys_
$files = glob($dir . '*.php'); // refresh list
foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    foreach ($masterTables as $tableSuffix) {
        foreach ($entities as $entity) {
            $oldName = $entity . '_' . $tableSuffix;
            $newName = 'master_' . $tableSuffix;
            if (strpos($content, $oldName) !== false) {
                $content = str_replace($oldName, $newName, $content);
                $changed = true;
            }
        }
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        echo "Updated foreign keys in " . basename($file) . "\n";
    }
}

echo "Migrations refactored successfully.\n";
