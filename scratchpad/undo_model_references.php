<?php

$dir = __DIR__ . '/../app/';

$masterModels = [
    'Product', 'Supplier', 'Customer', 'ProductCategory', 'Unit', 'Brand'
];

$entities = ['Gudang', 'Jihans', 'Hendhys'];

// Step 1: Delete duplicated master models
foreach ($entities as $entity) {
    foreach ($masterModels as $model) {
        $file = $dir . "Models/{$entity}/{$model}.php";
        if (file_exists($file)) {
            unlink($file);
            echo "Deleted $file\n";
        }
    }
}

// Step 2: Replace use statements in all PHP files
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $changed = false;
        
        foreach ($entities as $entity) {
            foreach ($masterModels as $model) {
                $oldUse = "use App\\Models\\{$entity}\\{$model};";
                $newUse = "use App\\Models\\{$model};";
                
                if (strpos($content, $oldUse) !== false) {
                    $content = str_replace($oldUse, $newUse, $content);
                    $changed = true;
                }
            }
        }
        
        if ($changed) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated use statements in: " . $file->getPathname() . "\n";
        }
    }
}

// Check database seeders too
$seedersDir = __DIR__ . '/../database/seeders/';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($seedersDir));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $changed = false;
        foreach ($entities as $entity) {
            foreach ($masterModels as $model) {
                $oldUse = "use App\\Models\\{$entity}\\{$model};";
                $newUse = "use App\\Models\\{$model};";
                if (strpos($content, $oldUse) !== false) {
                    $content = str_replace($oldUse, $newUse, $content);
                    $changed = true;
                }
            }
        }
        if ($changed) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated use statements in: " . $file->getPathname() . "\n";
        }
    }
}

echo "Model references fixed successfully.\n";
