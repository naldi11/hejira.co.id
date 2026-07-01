<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product as JihansProduct;
use App\Models\Unit;

class JihansRetailStock extends Model
{
    protected $table    = 'jihans_retail_stock';
    public    $timestamps = false;

    protected $fillable = ['product_id', 'quantity', 'unit_id', 'last_updated'];

    public function product(): BelongsTo { return $this->belongsTo(JihansProduct::class); }
    public function unit(): BelongsTo    { return $this->belongsTo(Unit::class); }
}
