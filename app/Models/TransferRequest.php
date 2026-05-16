<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferRequest extends Model
{
    protected $table = 'gudang_transfer_requests';

    protected $fillable = [
        'request_number', 'from_entity', 'branch_id', 'date', 'needed_date',
        'status', 'notes', 'rejection_reason',
        'requested_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'needed_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function requester(): BelongsTo    { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver(): BelongsTo     { return $this->belongsTo(User::class, 'approved_by'); }
    public function details(): HasMany        { return $this->hasMany(TransferRequestDetail::class, 'request_id'); }
    public function transferOuts(): HasMany   { return $this->hasMany(TransferOut::class, 'request_id'); }

    public function getFromEntityLabelAttribute(): string
    {
        return match($this->from_entity) {
            'jihans'  => "Jihan's Food",
            'hendhys' => $this->branch ? "Hendhys — {$this->branch->name}" : 'Hendhys Brownies',
            default   => $this->from_entity,
        };
    }
}
