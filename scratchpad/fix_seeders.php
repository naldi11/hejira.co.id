<?php

$dir = __DIR__ . '/../database/seeders/';
$files = [
    'MasterBrandSeeder.php',
    'MasterCategorySeeder.php',
    'MasterProductSeeder.php',
    'MasterSupplierCustomerSeeder.php',
    'UnitSeeder.php'
];

foreach ($files as $f) {
    $path = $dir . $f;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Remove the foreach loop start
        $content = str_replace(
            '$prefixes = [\'gudang\', \'jihans\', \'hendhys\'];
        foreach ($prefixes as $prefix) {', 
            '', 
            $content
        );
        $content = str_replace(
            '$prefixes = [\'gudang\', \'jihans\', \'hendhys\'];'."\n".'        foreach ($prefixes as $prefix) {', 
            '', 
            $content
        );
        // Replace dynamic table names
        $content = str_replace('$prefix . \'_brands\'', "'master_brands'", $content);
        $content = str_replace('$prefix . \'_product_categories\'', "'master_product_categories'", $content);
        $content = str_replace('$prefix . \'_products\'', "'master_products'", $content);
        $content = str_replace('$prefix . \'_suppliers\'', "'master_suppliers'", $content);
        $content = str_replace('$prefix . \'_customers\'', "'master_customers'", $content);
        $content = str_replace('$prefix . \'_units\'', "'master_units'", $content);
        
        // Find the last `}` before `}` of the run method. 
        // This is tricky. Let's just use regex to replace the final `        }`
        $content = preg_replace("/\n        }\n    }\n}/", "\n    }\n}", $content);
        
        file_put_contents($path, $content);
        echo "Fixed $f\n";
    }
}
