<?php

// Fix Jihans PosController
$jihansFile = __DIR__ . '/../app/Http/Controllers/Jihans/PosController.php';
$jihansContent = file_get_contents($jihansFile);
if (strpos($jihansContent, "->whereIn('entity_scope', ['jihans', 'all'])") === false) {
    $jihansContent = str_replace(
        "Product::where('status', 'active')", 
        "Product::where('status', 'active')->whereIn('master_products.entity_scope', ['jihans', 'all'])", 
        $jihansContent
    );
    $jihansContent = str_replace(
        "Customer::where('is_active', true)",
        "Customer::where('is_active', true)->whereIn('entity_scope', ['jihans', 'all'])",
        $jihansContent
    );
    file_put_contents($jihansFile, $jihansContent);
    echo "Fixed Jihans POS Controller\n";
}

// Fix Hendhys PosController
$hendhysFile = __DIR__ . '/../app/Http/Controllers/Hendhys/PosController.php';
$hendhysContent = file_get_contents($hendhysFile);
if (strpos($hendhysContent, "->whereIn('entity_scope', ['hendhys', 'all'])") === false) {
    $hendhysContent = str_replace(
        "Product::where('status', 'active')", 
        "Product::where('status', 'active')->whereIn('master_products.entity_scope', ['hendhys', 'all'])", 
        $hendhysContent
    );
    $hendhysContent = str_replace(
        "Customer::where('is_active', true)",
        "Customer::where('is_active', true)->whereIn('entity_scope', ['hendhys', 'all'])",
        $hendhysContent
    );
    file_put_contents($hendhysFile, $hendhysContent);
    echo "Fixed Hendhys POS Controller\n";
}

// Fix Master Data Index to filter by scope
$masterControllers = glob(__DIR__ . '/../app/Http/Controllers/Master/*.php');
foreach ($masterControllers as $file) {
    if (basename($file) === 'ScopesMasterData.php' || basename($file) === 'ReceiptController.php') continue;
    
    $content = file_get_contents($file);
    if (strpos($content, "->whereIn('entity_scope', [\$info['scope'], 'all'])") === false) {
        // Find $q = $this->getModelClass(...)::query()
        // Or $q = $modelClass::with(...)
        // Insert $q->whereIn('entity_scope', [$info['scope'], 'all']);
        
        // For ProductController:
        if (basename($file) === 'ProductController.php') {
            $content = str_replace(
                "\$q = \$modelClass::with(['category', 'unit', 'brand']);",
                "\$q = \$modelClass::with(['category', 'unit', 'brand'])->whereIn('entity_scope', [\$info['scope'], 'all']);",
                $content
            );
        } else {
            $content = preg_replace(
                "/\\\$q = \\\$this->getModelClass.*?query\(\);/",
                "$0\n        \$q->whereIn('entity_scope', [\$info['scope'], 'all']);",
                $content
            );
        }
        
        file_put_contents($file, $content);
        echo "Added query scope filter to " . basename($file) . "\n";
    }
    
    // Auto assign entity_scope on creation if not passed
    // Find $data['created_by'] = auth()->id();
    if (strpos($content, "\$data['entity_scope'] = \$request->input('entity_scope', \$info['scope']);") === false) {
        $content = preg_replace(
            "/\\\$data\['created_by'\] = auth\(\)->id\(\);/",
            "\$data['created_by'] = auth()->id();\n        \$data['entity_scope'] = \$request->input('entity_scope', \$info['scope'] === 'gudang' ? 'all' : \$info['scope']);",
            $content
        );
        file_put_contents($file, $content);
        echo "Added auto entity_scope assign to " . basename($file) . "\n";
    }
}
