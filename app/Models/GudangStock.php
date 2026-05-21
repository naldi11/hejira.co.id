<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Gudang\Unit as GudangUnit;
use App\Models\Gudang\Product as GudangProduct;

class GudangStock extends Model
{
    protected $table    = 'gudang_stock';
    public    $timestamps = false;

    protected $fillable = ['product_id', 'quantity', 'unit_id', 'last_updated'];

    protected function casts(): array
    {
        return [
            'quantity'     => 'decimal:3',
            'last_updated' => 'datetime',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(GudangProduct::class); }
    public function unit(): BelongsTo    { return $this->belongsTo(GudangUnit::class); }
}
