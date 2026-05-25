<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit;
use App\Models\Product;

class PoDetail extends Model
{
    protected $table    = 'gudang_po_details';
    public    $timestamps = false;

    protected $fillable = [
        'po_id', 'product_id', 'quantity_ordered', 'quantity_received',
        'unit_id', 'price', 'total', 'notes',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function unit(): BelongsTo    { return $this->belongsTo(Unit::class); }
    public function po(): BelongsTo      { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }

    public function remainingQty(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }
}
