<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit as HendhysUnit;
use App\Models\Product as HendhysProduct;

class HendhysStockPusat extends Model
{
    protected $table    = 'hendhys_stock_pusat';
    public    $timestamps = false;

    protected $fillable = ['product_id', 'quantity', 'quantity_return', 'unit_id', 'last_updated'];

    // quantity / quantity_return adalah decimal(15,3) — beri cast eksplisit agar
    // tidak dikembalikan sebagai string dan salah dipakai dalam aritmetika stok.
    protected function casts(): array
    {
        return [
            'quantity'        => 'integer',
            'quantity_return' => 'integer',
            'last_updated'    => 'datetime',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(HendhysProduct::class); }
    public function unit(): BelongsTo    { return $this->belongsTo(HendhysUnit::class); }
}
