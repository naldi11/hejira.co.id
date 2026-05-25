<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit;
use App\Models\Product;

class HendhysTransferToBranchDetail extends Model
{
    protected $table = 'hendhys_transfer_to_branch_details';
    public $timestamps = false;

    protected $fillable = [
        'transfer_id', 'product_id', 'quantity', 'received_quantity', 'kondisi', 'unit_id'
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(HendhysTransferToBranch::class, 'transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
