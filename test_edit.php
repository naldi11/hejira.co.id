<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::find(3); // kasir jihans
auth()->login($user);

$transaction = App\Models\JihansTransaction::find(53);

$request = Illuminate\Http\Request::create('/jihans/pos/53', 'PUT', [
    'transaction_date' => '2026-07-01',
    'customer_id' => 3,
    'customer_name' => 'ABC FROZEN',
    'customer_type' => 'Pelanggan Individual',
    'ppn_type' => 'none',
    'ppn_rate' => 11,
    'subtotal' => 435000,
    'discount_amount' => 0,
    'extra_discount' => 0,
    'tax_amount' => 0,
    'other_costs' => 50000,
    'grand_total' => 485000,
    'amount_paid' => 485000,
    'notes' => 'Test Edit',
    'items' => [
        [
            'product_id' => 90,
            'quantity' => 3,
            'price' => 145000,
            'discount' => 0,
            'total' => 435000
        ]
    ]
]);

$response = app()->handle($request);
echo $response->getContent();
