<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HendhysPendingTransaction extends Model
{
    protected $table = 'hendhys_pending_transactions';

    protected $fillable = [
        'pending_number', 'branch_id', 'date', 'customer_name', 
        'customer_id', 'customer_type', 'notes', 'created_by'
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
        return $this->hasMany(HendhysPendingDetail::class, 'pending_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
