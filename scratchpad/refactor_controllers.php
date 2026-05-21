<?php

$dir = __DIR__ . '/../app/Http/Controllers/Master/';

$controllers = [
    'BrandController' => 'Brand',
    'CustomerController' => 'Customer',
    'ProductCategoryController' => 'ProductCategory',
    'ProductController' => 'Product',
    'SupplierController' => 'Supplier',
    'UnitController' => 'Unit',
];

foreach ($controllers as $file => $model) {
    $path = $dir . $file . '.php';
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    
    // Remove USE statement for the model
    $content = preg_replace("/use App\\\\Models\\\\($model|ProductCategory|Unit|Brand);\n/", "", $content);

    // Replace Implicit binding: public function show(Request $request, Brand $brand) -> public function show(Request $request, $id)
    $varName = lcfirst(str_replace('ProductCategory', 'category', $model));
    $content = preg_replace("/public function (edit|update|destroy)\(Request \\\$request, $model \\\$$varName\)/", "public function $1(Request \$request, \$id)", $content);
    
    // Inject $modelName = $this->getModelClass('Model', $info['scope']);
    // inside index, create, store, edit, update, destroy
    
    // For ProductController which has multiple models:
    if ($model == 'Product') {
        $content = preg_replace("/Product::/", "\$this->getModelClass('Product', \$info['scope'])::", $content);
        $content = preg_replace("/ProductCategory::/", "\$this->getModelClass('ProductCategory', \$info['scope'])::", $content);
        $content = preg_replace("/Unit::/", "\$this->getModelClass('Unit', \$info['scope'])::", $content);
        $content = preg_replace("/Brand::/", "\$this->getModelClass('Brand', \$info['scope'])::", $content);
    } else {
        $content = preg_replace("/$model::/", "\$this->getModelClass('$model', \$info['scope'])::", $content);
    }
    
    // Replace whereIn entity_scope which we removed
    $content = preg_replace("/->whereIn\('entity_scope', \\\[\\\$info\['scope'\], 'all'\\\]\)/", "", $content);
    $content = preg_replace("/whereIn\('entity_scope', \\\[\\\$info\['scope'\], 'all'\\\]\)->/", "", $content);
    $content = preg_replace("/->where\('entity_scope', \\\$info\['scope'\]\)/", "", $content);
    $content = preg_replace("/'entity_scope'\s*=>\s*'nullable\|in:gudang,jihans,hendhys,all',/", "", $content);
    $content = preg_replace("/\\\$data\['entity_scope'\] = .*;/", "", $content);
    
    // Add findOrFail
    $content = preg_replace("/public function edit\(Request \\\$request, \\\$id\)\n\s*{\n\s*\\\$info = \\\$this->getScopeInfo\(\\\$request\);/", "public function edit(Request \$request, \$id)\n    {\n        \$info = \$this->getScopeInfo(\$request);\n        \$$varName = \$this->getModelClass('$model', \$info['scope'])::findOrFail(\$id);", $content);
    $content = preg_replace("/public function update\(Request \\\$request, \\\$id\)\n\s*{\n\s*\\\$info = \\\$this->getScopeInfo\(\\\$request\);/", "public function update(Request \$request, \$id)\n    {\n        \$info = \$this->getScopeInfo(\$request);\n        \$$varName = \$this->getModelClass('$model', \$info['scope'])::findOrFail(\$id);", $content);
    $content = preg_replace("/public function destroy\(Request \\\$request, \\\$id\)\n\s*{\n\s*\\\$info = \\\$this->getScopeInfo\(\\\$request\);/", "public function destroy(Request \$request, \$id)\n    {\n        \$info = \$this->getScopeInfo(\$request);\n        \$$varName = \$this->getModelClass('$model', \$info['scope'])::findOrFail(\$id);", $content);

    // Remove abort entity_scope checks
    $content = preg_replace("/if \(\\\$$varName->entity_scope.*abort\(403\);/", "", $content);
    
    file_put_contents($path, $content);
    echo "Refactored $file\n";
}
echo "Done.\n";
