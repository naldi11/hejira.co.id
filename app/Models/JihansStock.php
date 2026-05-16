<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JihansStock extends Model
{
    protected $table    = 'jihans_stock';
    public    $timestamps = false;

    protected $fillable = ['product_id', 'quantity', 'unit_id', 'last_updated'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function unit(): BelongsTo    { return $this->belongsTo(Unit::class); }
}
