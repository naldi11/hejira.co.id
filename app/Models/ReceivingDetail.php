<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivingDetail extends Model
{
    protected $table    = 'gudang_receiving_details';
    public    $timestamps = false;

    protected $fillable = [
        'receiving_id', 'product_id', 'quantity', 'unit_id', 'hpp_price', 'total', 'notes',
    ];

    public function product(): BelongsTo    { return $this->belongsTo(Product::class); }
    public function unit(): BelongsTo       { return $this->belongsTo(Unit::class); }
    public function receiving(): BelongsTo  { return $this->belongsTo(Receiving::class); }
}
