<?php

$dirs = [
    __DIR__ . '/../app/',
    __DIR__ . '/../database/',
    __DIR__ . '/../resources/',
    __DIR__ . '/../routes/'
];

$replacements = [];
$entities = ['gudang', 'jihans', 'hendhys'];
$masters = ['products', 'suppliers', 'customers', 'product_categories', 'units', 'brands'];

foreach ($entities as $entity) {
    foreach ($masters as $master) {
        $replacements["{$entity}_{$master}"] = "master_{$master}";
    }
}

function processDirectory($dir, $replacements) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'js', 'vue', 'blade.php'])) {
            $content = file_get_contents($file->getPathname());
            $changed = false;
            foreach ($replacements as $old => $new) {
                if (strpos($content, $old) !== false) {
                    $content = str_replace($old, $new, $content);
                    $changed = true;
                }
            }
            if ($changed) {
                file_put_contents($file->getPathname(), $content);
                echo "Updated: " . $file->getPathname() . "\n";
            }
        }
    }
}

foreach ($dirs as $dir) {
    processDirectory($dir, $replacements);
}

echo "All code references updated successfully.\n";
