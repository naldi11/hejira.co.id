<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product as JihansProduct;
use App\Models\Unit as JihansUnit;

class JihansTransactionDetail extends Model
{
    protected $table = 'jihans_transaction_details';
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_id',
        'price',
        'discount_percent',
        'discount_amount',
        'total',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(JihansTransaction::class, 'transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(JihansProduct::class)->withTrashed();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(JihansUnit::class);
    }
}
