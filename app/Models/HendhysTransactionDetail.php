<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product as HendhysProduct;
use App\Models\Hendhys\Unit as HendhysUnit;

class HendhysTransactionDetail extends Model
{
    protected $table = 'hendhys_transaction_details';

    protected $fillable = [
        'transaction_id', 'product_id', 'product_name', 'quantity', 'unit_id',
        'price', 'discount_amount', 'total'
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(HendhysTransaction::class, 'transaction_id');
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
