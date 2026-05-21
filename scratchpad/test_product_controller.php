<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $req = Illuminate\Http\Request::create('/hendhys/master/products', 'GET');
    // Set route in request so getPrefix() works
    $router = app('router');
    $route = $router->getRoutes()->match($req);
    $req->setRouteResolver(function() use ($route) { return $route; });

    $c = app()->make(App\Http\Controllers\Master\ProductController::class);
    $c->index($req);
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
