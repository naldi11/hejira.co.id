<?php

$dir = __DIR__ . '/../app/Models/';

$entities = ['Gudang', 'Jihans', 'Hendhys'];
$models = [
    'Supplier' => 'suppliers',
    'Customer' => 'customers',
    'ProductCategory' => 'product_categories',
    'Unit' => 'units',
    'Brand' => 'brands',
    'Product' => 'products',
];

$relationships = [
    'Product' => [
        'category' => 'ProductCategory',
        'unit' => 'Unit',
        'brand' => 'Brand',
    ]
];

foreach ($entities as $entity) {
    $entityDir = $dir . $entity;
    if (!is_dir($entityDir)) {
        mkdir($entityDir, 0755, true);
    }

    foreach ($models as $modelName => $tableName) {
        $fullTableName = strtolower($entity) . '_' . $tableName;
        
        $content = "<?php\n\nnamespace App\Models\\$entity;\n\n";
        $content .= "use App\Models\\$modelName as BaseModel;\n";
        $content .= "use Illuminate\Database\Eloquent\Relations\BelongsTo;\n\n";
        $content .= "class $modelName extends BaseModel\n{\n";
        $content .= "    protected \$table = '$fullTableName';\n";
        
        if (isset($relationships[$modelName])) {
            $content .= "\n";
            foreach ($relationships[$modelName] as $relName => $relClass) {
                $content .= "    public function $relName(): BelongsTo\n    {\n";
                if ($relName === 'category') {
                    $content .= "        return \$this->belongsTo($relClass::class, 'category_id');\n";
                } else {
                    $content .= "        return \$this->belongsTo($relClass::class);\n";
                }
                $content .= "    }\n";
            }
        }
        
        $content .= "}\n";
        
        file_put_contents($entityDir . '/' . $modelName . '.php', $content);
        echo "Created App\\Models\\$entity\\$modelName\n";
    }
}
echo "Done.\n";
