<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hendhys\Unit as HendhysUnit;
use App\Models\Product as HendhysProduct;

class HendhysPendingDetail extends Model
{
    protected $table = 'hendhys_pending_details';

    protected $fillable = [
        'pending_id', 'product_id', 'product_name', 'quantity', 'unit_id', 'price', 'discount_percent', 'total'
    ];

    public function pendingTransaction(): BelongsTo
    {
        return $this->belongsTo(HendhysPendingTransaction::class, 'pending_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(HendhysProduct::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(HendhysUnit::class);
    }
}
