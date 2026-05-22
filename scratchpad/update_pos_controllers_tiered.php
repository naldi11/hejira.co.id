<?php

$jihansFile = __DIR__ . '/../app/Http/Controllers/Jihans/PosController.php';
if (file_exists($jihansFile)) {
    $content = file_get_contents($jihansFile);
    // Replace with(['unit', 'category']) with with(['unit', 'category', 'tieredPrices'])
    if (strpos($content, "->with(['unit', 'category', 'tieredPrices'])") === false) {
        $content = str_replace("->with(['unit', 'category'])", "->with(['unit', 'category', 'tieredPrices'])", $content);
        file_put_contents($jihansFile, $content);
        echo "Fixed Jihans POS Controller\n";
    }
}

$hendhysFile = __DIR__ . '/../app/Http/Controllers/Hendhys/PosController.php';
if (file_exists($hendhysFile)) {
    $content = file_get_contents($hendhysFile);
    if (strpos($content, "->with(['unit', 'tieredPrices'])") === false) {
        // Change ->with('unit') to ->with(['unit', 'tieredPrices'])
        $content = str_replace("->with('unit')", "->with(['unit', 'tieredPrices'])", $content);
        file_put_contents($hendhysFile, $content);
        echo "Fixed Hendhys POS Controller\n";
    }
}
