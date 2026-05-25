<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receiving extends Model
{
    protected $table = 'gudang_receivings';

    protected $fillable = [
        'grn_number', 'po_id', 'supplier_id', 'date', 'notes', 'created_by',
        'status', 'received_by_name', 'supplier_rep_name',
        'kendala', 'closed_at', 'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function po(): BelongsTo         { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function supplier(): BelongsTo   { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function closedBy(): BelongsTo   { return $this->belongsTo(User::class, 'closed_by'); }
    public function details(): HasMany      { return $this->hasMany(ReceivingDetail::class, 'receiving_id'); }
    public function photos(): HasMany       { return $this->hasMany(ReceivingPhoto::class, 'receiving_id'); }

    public function isOpen(): bool   { return $this->status === 'open'; }
    public function isClosed(): bool { return $this->status === 'closed'; }
}
