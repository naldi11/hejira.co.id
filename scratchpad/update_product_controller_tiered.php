<?php

$controllerFile = __DIR__ . '/../app/Http/Controllers/Master/ProductController.php';

if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    
    // Add tiered_prices validation and creation logic to store()
    $storeValidation = "
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'tiered_prices' => 'nullable|array',
            'tiered_prices.*.min_qty' => 'required_with:tiered_prices|numeric|min:1',
            'tiered_prices.*.price' => 'required_with:tiered_prices|numeric|min:0',
        ], [";
    $content = preg_replace("/\n\s*'notes' => 'nullable\|string',\n\s*'image' => 'nullable\|image\|max:2048',\n\s*], \[/", $storeValidation, $content);
    
    $storeLogic = "
        \$product = \$this->getModelClass('Product', \$info['scope'])::create(\$data);
        
        if (\$request->has('tiered_prices') && is_array(\$request->tiered_prices)) {
            foreach (\$request->tiered_prices as \$tier) {
                if (!empty(\$tier['min_qty']) && !empty(\$tier['price'])) {
                    \$product->tieredPrices()->create([
                        'min_qty' => \$tier['min_qty'],
                        'price' => \$tier['price']
                    ]);
                }
            }
        }
        
        \$this->logger->log('create', 'master.product', \"Tambah produk: {\$product->name}\", \$product);
";
    $content = preg_replace("/\\\$product = \\\$this->getModelClass\('Product', \\\$info\['scope'\]\)::create\(\\\$data\);\n\s*\\\$this->logger->log\('create', 'master.product', \"Tambah produk: \{\\\$product->name\}\", \\\$product\);/", $storeLogic, $content);

    // Add with('tieredPrices') to edit()
    $content = preg_replace("/\\\$product = \\\$this->getModelClass\('Product', \\\$info\['scope'\]\)::findOrFail\(\\\$id\);/", "\$product = \$this->getModelClass('Product', \$info['scope'])::with('tieredPrices')->findOrFail(\$id);", $content);

    // Add tiered_prices validation and update logic to update()
    $updateValidation = "
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'tiered_prices' => 'nullable|array',
            'tiered_prices.*.min_qty' => 'required_with:tiered_prices|numeric|min:1',
            'tiered_prices.*.price' => 'required_with:tiered_prices|numeric|min:0',
        ], [";
    $content = preg_replace("/\n\s*'notes' => 'nullable\|string',\n\s*'image' => 'nullable\|image\|max:2048',\n\s*], \[/", $updateValidation, $content, 1); // Limit to 1 for update method (second match)
    
    $updateLogic = "
        \$product->update(\$data);
        
        \$product->tieredPrices()->delete();
        if (\$request->has('tiered_prices') && is_array(\$request->tiered_prices)) {
            foreach (\$request->tiered_prices as \$tier) {
                if (!empty(\$tier['min_qty']) && !empty(\$tier['price'])) {
                    \$product->tieredPrices()->create([
                        'min_qty' => \$tier['min_qty'],
                        'price' => \$tier['price']
                    ]);
                }
            }
        }
";
    $content = preg_replace("/\\\$product->update\(\\\$data\);/", $updateLogic, $content);

    file_put_contents($controllerFile, $content);
    echo "Fixed ProductController.php\n";
}
