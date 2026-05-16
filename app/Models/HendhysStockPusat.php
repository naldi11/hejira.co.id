<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HendhysStockPusat extends Model
{
    protected $table    = 'hendhys_stock_pusat';
    public    $timestamps = false;

    protected $fillable = ['product_id', 'quantity', 'unit_id', 'last_updated'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function unit(): BelongsTo    { return $this->belongsTo(Unit::class); }
}
