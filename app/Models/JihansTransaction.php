<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Jihans\Customer as JihansCustomer;

class JihansTransaction extends Model
{
    protected $table = 'jihans_transactions';

    protected $fillable = [
        'transaction_number',
        'date',
        'time',
        'customer_id',
        'customer_name',
        'customer_type',
        'ppn_type',
        'ppn_rate',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'other_costs',
        'grand_total',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(JihansCustomer::class, 'customer_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(JihansTransactionDetail::class, 'transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(JihansTransactionPayment::class, 'transaction_id');
    }
}
