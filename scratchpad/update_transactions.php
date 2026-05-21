<?php
$dirs = [
    'Jihans' => __DIR__ . '/../app/Http/Controllers/Jihans/',
    'Hendhys' => __DIR__ . '/../app/Http/Controllers/Hendhys/',
    'Gudang' => __DIR__ . '/../app/Http/Controllers/Gudang/',
];

$masters = ['master_products', 'master_customers', 'master_product_categories', 'master_units', 'master_brands', 'master_suppliers', 'master_users', 'master_branches'];

foreach ($dirs as $entity => $dir) {
    if (!is_dir($dir)) continue;
    $files = glob($dir . '*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $changed = false;
        
        $prefix = strtolower($entity);
        
        // We DO NOT REPLACE master_users and master_branches because they are not split.
        $mastersToReplace = ['master_products', 'master_customers', 'master_product_categories', 'master_units', 'master_brands', 'master_suppliers'];
        
        foreach ($mastersToReplace as $master) {
            if (strpos($content, $master) !== false) {
                $replacement = str_replace('master_', $prefix . '_', $master);
                $content = str_replace($master, $replacement, $content);
                $changed = true;
            }
        }
        
        // Fix models import: use App\Models\Product; -> use App\Models\Jihans\Product;
        $models = ['Product', 'Customer', 'ProductCategory', 'Unit', 'Brand', 'Supplier'];
        foreach ($models as $model) {
            $search = "use App\\Models\\$model;";
            $replace = "use App\\Models\\$entity\\$model;";
            if (strpos($content, $search) !== false) {
                $content = str_replace($search, $replace, $content);
                $changed = true;
            }
        }
        
        if ($changed) {
            file_put_contents($file, $content);
            echo "Updated $file\n";
        }
    }
}
echo "Done.\n";
