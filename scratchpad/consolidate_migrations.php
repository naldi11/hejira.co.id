<?php

$dir = __DIR__ . '/../database/migrations/';

$filesToDelete = [
    '2026_05_17_034747_rename_entity_to_entity_scope_in_categories.php',
    '2026_05_17_034822_add_entity_scope_to_all_master_tables.php',
    '2026_05_20_122152_add_image_to_products_tables.php',
    '2026_05_21_000623_drop_jenis_from_products_table.php'
];

foreach ($filesToDelete as $f) {
    if (file_exists($dir . $f)) {
        unlink($dir . $f);
        echo "Deleted $f\n";
    }
}

// 1. Update master_product_categories (change entity to entity_scope)
$catFile = $dir . '2026_05_16_120103_create_master_product_categories_table.php';
if (file_exists($catFile)) {
    $content = file_get_contents($catFile);
    $content = str_replace("->enum('entity',", "->enum('entity_scope',", $content);
    file_put_contents($catFile, $content);
    echo "Updated categories\n";
}

// 2. Add entity_scope to units, brands, suppliers, customers
$tables = ['units', 'brands', 'suppliers', 'customers'];
foreach ($tables as $t) {
    $files = glob($dir . '*_create_master_' . $t . '_table.php');
    if (!empty($files)) {
        $file = $files[0];
        $content = file_get_contents($file);
        if (strpos($content, 'entity_scope') === false) {
            $content = str_replace('$table->id();', "\$table->id();\n            \$table->enum('entity_scope', ['gudang', 'jihans', 'hendhys', 'all'])->default('all');", $content);
            file_put_contents($file, $content);
            echo "Updated $t\n";
        }
    }
}

// 3. Update products (add image, remove jenis)
$prodFile = $dir . '2026_05_16_120106_create_master_products_table.php';
if (file_exists($prodFile)) {
    $content = file_get_contents($prodFile);
    if (strpos($content, 'image') === false) {
        $content = str_replace('$table->string(\'name\', 200);', "\$table->string('name', 200);\n            \$table->string('image')->nullable();", $content);
    }
    // Remove jenis
    $content = preg_replace("/\\\$table->enum\('jenis'.*?\n/", "", $content);
    file_put_contents($prodFile, $content);
    echo "Updated products\n";
}

echo "Base migrations consolidated.\n";
