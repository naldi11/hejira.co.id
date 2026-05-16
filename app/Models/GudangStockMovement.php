<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudangStockMovement extends Model
{
    protected $table      = 'gudang_stock_movements';
    public    $timestamps = false;

    protected $fillable = [
        'product_id', 'type', 'source', 'reference_id',
        'quantity', 'quantity_before', 'quantity_after',
        'notes', 'created_by', 'created_at',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
