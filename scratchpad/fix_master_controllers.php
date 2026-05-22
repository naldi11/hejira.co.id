<?php

$dir = __DIR__ . '/../app/Http/Controllers/Master/';
$files = glob($dir . '*.php');

foreach ($files as $file) {
    if (basename($file) === 'ScopesMasterData.php') {
        $content = file_get_contents($file);
        // Remove the namespace logic
        $content = preg_replace(
            "/\$namespace = match\(\\\$scope\) \{.*?\};/s",
            "",
            $content
        );
        $content = str_replace(
            'return "App\\\\Models\\\\{$namespace}\\\\{$modelName}";',
            'return "App\\\\Models\\\\{$modelName}";',
            $content
        );
        file_put_contents($file, $content);
        echo "Updated ScopesMasterData.php\n";
        continue;
    }

    $content = file_get_contents($file);
    $changed = false;

    // Replace {$info['scope']}_table with master_table
    $replacements = [
        "{\$info['scope']}_product_categories" => "master_product_categories",
        "{\$info['scope']}_units" => "master_units",
        "{\$info['scope']}_brands" => "master_brands",
        "{\$info['scope']}_suppliers" => "master_suppliers",
        "{\$info['scope']}_customers" => "master_customers",
        "{\$info['scope']}_products" => "master_products",
    ];

    foreach ($replacements as $old => $new) {
        if (strpos($content, $old) !== false) {
            $content = str_replace($old, $new, $content);
            $changed = true;
        }
    }

    // $tableName = strtolower($info['scope']) . '_products'; => $tableName = 'master_products';
    if (preg_match("/\\\$tableName = strtolower\(\\\$info\['scope'\]\) \. '_(.*?)';/", $content, $matches)) {
        $content = preg_replace("/\\\$tableName = strtolower\(\\\$info\['scope'\]\) \. '_(.*?)';/", "\$tableName = 'master_$1';", $content);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($file, $content);
        echo "Updated " . basename($file) . "\n";
    }
}
