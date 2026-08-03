<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product as JihansProduct;

class JihansRetailStockMovement extends Model
{
    protected $table    = 'jihans_retail_stock_movements';
    public $timestamps  = false; // We use created_at manually in migration

    protected $fillable = [
        'product_id',
        'type',
        'source',
        'reference_id',
        'quantity',
        'quantity_before',
        'quantity_after',
        'notes',
        'created_by',
        'created_at',
    ];

    // Disamakan dengan JihansGudangStockMovement. Tanpa cast ini `created_at`
    // dikembalikan sebagai string mentah (karena $timestamps = false), sehingga
    // pemanggil yang memperlakukannya sebagai Carbon akan error.
    protected function casts(): array
    {
        return [
            'quantity'        => 'integer',
            'quantity_before' => 'integer',
            'quantity_after'  => 'integer',
            'created_at'      => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(JihansProduct::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
