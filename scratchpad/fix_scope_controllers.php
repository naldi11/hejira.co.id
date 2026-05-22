<?php

$files = [
    __DIR__ . '/../app/Http/Controllers/Master/BrandController.php',
    __DIR__ . '/../app/Http/Controllers/Master/ProductCategoryController.php',
    __DIR__ . '/../app/Http/Controllers/Master/UnitController.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Remove $data['entity'] = ... if exists
        $content = preg_replace("/\\\$data\['entity'\] = \\\$info\['scope'\];/", "", $content);
        
        // Fix $data['entity_scope']
        $content = str_replace(
            "\$data['entity_scope'] = \$info['scope'];",
            "\$data['entity_scope'] = \$request->input('entity_scope', \$info['scope'] === 'gudang' ? 'all' : \$info['scope']);",
            $content
        );
        
        file_put_contents($file, $content);
        echo "Fixed " . basename($file) . "\n";
    }
}
