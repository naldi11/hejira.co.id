<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product as JihansProduct;
use App\Models\Unit;

class JihansStockInDetail extends Model
{
    protected $table    = 'jihans_stock_in_details';
    public    $timestamps = false;

    protected $fillable = ['stock_in_id', 'product_id', 'quantity', 'unit_id', 'hpp_price', 'notes'];

    public function product(): BelongsTo { return $this->belongsTo(JihansProduct::class); }
    public function unit(): BelongsTo    { return $this->belongsTo(Unit::class); }
}
