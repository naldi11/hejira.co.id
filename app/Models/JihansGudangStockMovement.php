<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit;
use App\Models\Product;

class JihansGudangStockMovement extends Model
{
    protected $table      = 'jihans_gudang_stock_movements';
    public    $timestamps = false;

    protected $fillable = [
        'product_id', 'type', 'source', 'reference_id',
        'quantity', 'quantity_before', 'quantity_after',
        'notes', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function getDocumentNumberAttribute(): ?string
    {
        if (!$this->reference_id) {
            return null;
        }

        return match ($this->source) {
            'transfer_out'       => \App\Models\TransferOut::where('id', $this->reference_id)->value('transfer_number'),
            'purchase_receiving', 'receiving' => \App\Models\Receiving::where('id', $this->reference_id)->value('grn_number'),
            'return_receiving', 'return_receive'  => \App\Models\GudangReturn::where('id', $this->reference_id)->value('return_number'),
            default              => null,
        };
    }
}
