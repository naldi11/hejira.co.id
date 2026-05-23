<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HendhysTransactionPayment extends Model
{
    protected $table = 'hendhys_transaction_payments';

    protected $fillable = [
        'transaction_id', 'payment_method_id', 'payment_method', 'amount', 'bank_name', 'reference_number'
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(HendhysTransaction::class, 'transaction_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id')->withTrashed();
    }
}
