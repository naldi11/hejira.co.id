<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $table = 'gudang_purchase_orders';

    protected $fillable = [
        'po_number', 'supplier_id', 'date', 'expected_date',
        'status', 'total_amount', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'expected_date' => 'date',
            'total_amount'  => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo   { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function details(): HasMany      { return $this->hasMany(PoDetail::class, 'po_id'); }
    public function receivings(): HasMany   { return $this->hasMany(Receiving::class, 'po_id'); }

    /** Status di mana PO masih boleh diterima barangnya (dipakai juga oleh scope). */
    public const RECEIVABLE_STATUSES = ['draft', 'sent', 'partial'];

    /** Status di mana PO masih boleh dibatalkan. */
    public const CANCELLABLE_STATUSES = ['draft', 'sent'];

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    // Barang bisa diterima selama PO belum cancelled/completed
    public function isReceivable(): bool
    {
        return in_array($this->status, self::RECEIVABLE_STATUSES, true);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, self::CANCELLABLE_STATUSES, true);
    }

    /** Satu-satunya definisi "PO masih bisa diterima" untuk query builder. */
    public function scopeReceivable($query)
    {
        return $query->whereIn('status', self::RECEIVABLE_STATUSES);
    }
}
