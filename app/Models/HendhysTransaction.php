<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HendhysTransaction extends Model
{
    protected $table = 'hendhys_transactions';

    protected $fillable = [
        'transaction_number', 'branch_id', 'date', 'time', 'customer_name',
        'customer_id', 'customer_type', 'subtotal', 'discount_amount',
        'ppn_type', 'tax_amount', 'other_costs', 'grand_total',
        'status', 'notes', 'created_by'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(HendhysTransactionDetail::class, 'transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HendhysTransactionPayment::class, 'transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
