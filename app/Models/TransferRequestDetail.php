<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit;
use App\Models\Product;

class TransferRequestDetail extends Model
{
    protected $table    = 'gudang_transfer_request_details';
    public    $timestamps = false;

    protected $fillable = [
        'request_id', 'product_id', 'quantity_requested', 'quantity_approved', 'unit_id', 'notes',
    ];

    public function request(): BelongsTo  { return $this->belongsTo(TransferRequest::class, 'request_id'); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
    public function unit(): BelongsTo     { return $this->belongsTo(Unit::class); }
}
