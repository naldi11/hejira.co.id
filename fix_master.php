<?php
// Force clean UTF-8 by re-reading and writing specific master data files
$files = [
    'resources/views/master/units/index.blade.php',
    'resources/views/master/products/index.blade.php',
    'resources/views/master/categories/index.blade.php',
    'resources/views/master/brands/index.blade.php',
];

foreach ($files as $path) {
    if (!file_exists($path)) {
        continue;
    }

    $bytes = file_get_contents($path);

    // Remove BOM if exists
    if (substr($bytes, 0, 3) === "\xEF\xBB\xBF") {
        $bytes = substr($bytes, 3);
    }

    // Fix known mojibake patterns
    $fixes = [
        "\xC3\x82\xC2\xB7" => "-",    // Replace Â· with -
        "Â·" => "-",
        "â€“" => "-",
        "â€”" => "-",
    ];

    $clean = $bytes;
    foreach ($fixes as $bad => $good) {
        $clean = str_replace($bad, $good, $clean);
    }

    file_put_contents($path, $clean);
    if ($clean !== $bytes) {
        echo "Fixed: $path\n";
    } else {
        echo "Already clean: $path\n";
    }
}
echo "Done.\n";
