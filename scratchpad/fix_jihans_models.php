<?php

// 1. Fix Jihans Models
$jihansModels = glob(__DIR__ . '/../app/Models/Jihans*.php');
foreach ($jihansModels as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'use App\Models\Jihans\Product as JihansProduct;') !== false) {
        $content = str_replace(
            "use App\Models\Jihans\Product as JihansProduct;",
            "use App\Models\Product as JihansProduct;",
            $content
        );
        file_put_contents($file, $content);
        echo "Fixed " . basename($file) . "\n";
    }
}

// 2. Fix Hendhys Models
$hendhysModels = glob(__DIR__ . '/../app/Models/Hendhys*.php');
foreach ($hendhysModels as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'use App\Models\Hendhys\Product as HendhysProduct;') !== false) {
        $content = str_replace(
            "use App\Models\Hendhys\Product as HendhysProduct;",
            "use App\Models\Product as HendhysProduct;",
            $content
        );
        file_put_contents($file, $content);
        echo "Fixed " . basename($file) . "\n";
    }
}

// 3. Fix View
$dashboardFile = __DIR__ . '/../resources/views/jihans/dashboard.blade.php';
if (file_exists($dashboardFile)) {
    $content = file_get_contents($dashboardFile);
    $content = str_replace(
        "\App\Models\Jihans\Product::where('status', 'active')",
        "\App\Models\Product::where('status', 'active')->whereIn('master_products.entity_scope', ['jihans', 'all'])",
        $content
    );
    file_put_contents($dashboardFile, $content);
    echo "Fixed Jihans dashboard view\n";
}

// 4. Also check Hendhys Dashboard View just in case
$hDashboardFile = __DIR__ . '/../resources/views/hendhys/dashboard.blade.php';
if (file_exists($hDashboardFile)) {
    $content = file_get_contents($hDashboardFile);
    if (strpos($content, '\App\Models\Hendhys\Product::where') !== false) {
        $content = str_replace(
            "\App\Models\Hendhys\Product::where('status', 'active')",
            "\App\Models\Product::where('status', 'active')->whereIn('master_products.entity_scope', ['hendhys', 'all'])",
            $content
        );
        file_put_contents($hDashboardFile, $content);
        echo "Fixed Hendhys dashboard view\n";
    }
}
