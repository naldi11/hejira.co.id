<?php
$files = [
    __DIR__ . '/../database/seeders/UnitSeeder.php',
    __DIR__ . '/../database/seeders/JihansTransactionSeeder.php',
    __DIR__ . '/../database/seeders/HendhysTransactionSeeder.php',
    __DIR__ . '/../database/seeders/GudangTransactionSeeder.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace('master_units', 'jihans_units', $content);
        $content = str_replace('master_products', 'jihans_products', $content);
        file_put_contents($file, $content);
        echo "Patched $file\n";
    }
}
echo "Done.\n";
