<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HendhysTransferToBranch extends Model
{
    protected $table = 'hendhys_transfer_to_branch';

    protected $fillable = [
        'transfer_number', 'request_id', 'branch_id', 'date',
        'status', 'notes', 'receive_notes', 'receive_photo',
        'receive_kendala', 'receive_received_by_name', 'receive_pengirim_name',
        'created_by', 'received_by'
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function branchRequest(): BelongsTo
    {
        return $this->belongsTo(HendhysBranchRequest::class, 'request_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(HendhysTransferToBranchDetail::class, 'transfer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(HendhysTransferToBranchPhoto::class, 'transfer_id');
    }
}
