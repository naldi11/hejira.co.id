<?php
$files = [
    __DIR__ . '/../database/seeders/UnitSeeder.php',
    __DIR__ . '/../database/seeders/MasterCategorySeeder.php',
    __DIR__ . '/../database/seeders/MasterBrandSeeder.php',
    __DIR__ . '/../database/seeders/MasterSupplierCustomerSeeder.php',
    __DIR__ . '/../database/seeders/MasterProductSeeder.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // We will use preg_replace to wrap DB::table('jihans_...')->... inside a foreach loop.
    // Instead of regex, let's just create a custom search & replace because the seeders have very predictable DB::table calls.
    
    // For each file, we want to replace `DB::table('jihans_` with `DB::table($prefix . '_`
    // And we need to add `$prefixes = ['gudang', 'jihans', 'hendhys'];` at the start of the `run` method,
    // and wrap the insertions in a `foreach ($prefixes as $prefix) { ... }`
    
    if (strpos($content, '$prefixes = [\'gudang\', \'jihans\', \'hendhys\'];') === false) {
        $content = str_replace('public function run(): void
    {', "public function run(): void\n    {\n        \$prefixes = ['gudang', 'jihans', 'hendhys'];\n        foreach (\$prefixes as \$prefix) {\n", $content);
        
        $content = str_replace('DB::table(\'jihans_', 'DB::table($prefix . \'_', $content);
        
        // At the end of the run method, close the foreach loop
        $content = str_replace('    }
}', "        }\n    }\n}", $content);
        
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
echo "Done.\n";
