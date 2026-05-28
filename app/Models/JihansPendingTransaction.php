<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Customer as JihansCustomer;

class JihansPendingTransaction extends Model
{
    protected $table = 'jihans_pending_transactions';

    protected $fillable = [
        'pending_number',
        'date',
        'customer_id',
        'customer_name',
        'customer_type',
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
        return $this->hasMany(JihansPendingDetail::class, 'pending_id');
    }
}
