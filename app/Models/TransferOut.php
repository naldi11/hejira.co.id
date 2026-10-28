<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class TransferOut extends Model
{
    protected $table = 'gudang_transfer_out';

    protected $fillable = [
        'transfer_number', 'request_id', 'to_entity', 'branch_id',
        'date', 'notes', 'created_by', 'status', 'received_by', 'receive_notes', 'receive_photo',
        'receive_kendala', 'receive_received_by_name', 'receive_pengirim_name', 'received_at',
        'request_key', 'payload_hash', 'destination_name', 'sender_name',
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
    public function receiptConfirmation(): MorphOne { return $this->morphOne(ReceiptConfirmation::class, 'receiptable'); }

    public function getDestinationLabelAttribute(): string
    {
        return match ($this->to_entity) {
            'izzi' => 'Izzi Kebab — '.($this->destination_name ?? $this->branch?->name ?? 'Cabang tidak tersedia'),
            'hendhys' => 'Hendhys — '.($this->destination_name ?? $this->branch?->name ?? 'Pusat Produksi'),
            default => $this->destination_name ?? 'Jihan’s — Stok Produksi',
        };
    }
}
