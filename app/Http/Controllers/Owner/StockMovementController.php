<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\JihansGudangStockMovement;
use App\Services\OwnerStockMovement;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function show(JihansGudangStockMovement $movement, OwnerStockMovement $details)
    {
        return Inertia::render('Owner/StockMovement', $details->show($movement));
    }
}
