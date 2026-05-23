<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JihansTransactionPayment extends Model
{
    protected $table = 'jihans_transaction_payments';
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'payment_method_id',
        'payment_method',
        'amount',
        'reference_number',
        'bank_name',
        'notes',
        'created_at',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(JihansTransaction::class, 'transaction_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id')->withTrashed();
    }
}
