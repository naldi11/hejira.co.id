<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HendhysBranchRequest extends Model
{
    protected $table = 'hendhys_branch_requests';

    protected $fillable = [
        'request_number', 'branch_id', 'date', 'status', 
        'notes', 'rejection_reason', 'requested_by', 'approved_by', 'approved_at'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(HendhysBranchRequestDetail::class, 'request_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transferOuts(): HasMany
    {
        return $this->hasMany(HendhysTransferToBranch::class, 'request_id');
    }
}
