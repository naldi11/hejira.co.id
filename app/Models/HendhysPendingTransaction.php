<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Customer as HendhysCustomer;

class HendhysPendingTransaction extends Model
{
    protected $table = 'hendhys_pending_transactions';

    protected $fillable = [
        'pending_number', 'branch_id', 'date', 'customer_name',
        'customer_phone', 'customer_id', 'customer_type', 'notes', 'created_by'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(HendhysCustomer::class, 'customer_id', 'id');
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
