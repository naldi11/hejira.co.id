<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferOutDetail extends Model
{
    protected $table    = 'gudang_transfer_out_details';
    public    $timestamps = false;

    protected $fillable = [
        'transfer_id', 'product_id', 'quantity', 'unit_id', 'hpp_price', 'total',
    ];

    public function transfer(): BelongsTo { return $this->belongsTo(TransferOut::class, 'transfer_id'); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
    public function unit(): BelongsTo     { return $this->belongsTo(Unit::class); }
}
