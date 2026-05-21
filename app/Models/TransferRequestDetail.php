<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Gudang\Unit as GudangUnit;
use App\Models\Gudang\Product as GudangProduct;

class TransferRequestDetail extends Model
{
    protected $table    = 'gudang_transfer_request_details';
    public    $timestamps = false;

    protected $fillable = [
        'request_id', 'product_id', 'quantity_requested', 'quantity_approved', 'unit_id', 'notes',
    ];

    public function request(): BelongsTo  { return $this->belongsTo(TransferRequest::class, 'request_id'); }
    public function product(): BelongsTo  { return $this->belongsTo(GudangProduct::class); }
    public function unit(): BelongsTo     { return $this->belongsTo(GudangUnit::class); }
}
