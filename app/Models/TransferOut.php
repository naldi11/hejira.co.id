<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferOut extends Model
{
    protected $table = 'gudang_transfer_out';

    protected $fillable = [
        'transfer_number', 'request_id', 'to_entity', 'branch_id',
        'date', 'notes', 'created_by', 'status', 'received_by', 'receive_notes', 'receive_photo',
        'receive_kendala', 'receive_received_by_name', 'receive_pengirim_name', 'received_at',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo  { return $this->belongsTo(TransferRequest::class, 'request_id'); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function details(): HasMany    { return $this->hasMany(TransferOutDetail::class, 'transfer_id'); }
    public function photos(): HasMany     { return $this->hasMany(TransferOutPhoto::class, 'transfer_id'); }

    public function isPending(): bool    { return $this->status === 'sent'; }
    public function isReceived(): bool   { return $this->status === 'received'; }

    public function getToEntityLabelAttribute(): string
    {
        return match($this->to_entity) {
            'jihans'  => "Jihan's Food",
            'hendhys' => $this->branch ? "Hendhys — {$this->branch->name}" : 'Hendhys Brownies',
            default   => $this->to_entity,
        };
    }
}
