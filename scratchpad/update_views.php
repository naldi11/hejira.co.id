<?php

$files = [
    __DIR__ . '/../resources/views/jihans/dashboard.blade.php' => 'jihans_products',
    __DIR__ . '/../resources/views/hendhys/dashboard.blade.php' => 'hendhys_products',
];

foreach ($files as $file => $replacement) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace('master_products', $replacement, $content);
        file_put_contents($file, $content);
        echo "Updated $file with $replacement\n";
    }
}
echo "Done.\n";
