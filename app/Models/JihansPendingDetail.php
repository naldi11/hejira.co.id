<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product as JihansProduct;
use App\Models\Unit as JihansUnit;

class JihansPendingDetail extends Model
{
    protected $table = 'jihans_pending_details';
    public $timestamps = false;

    protected $fillable = [
        'pending_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_id',
        'price',
        'discount_percent',
        'total',
    ];

    /**
     * Kuantitas stok WAJIB integer; hanya nilai uang yang desimal.
     * Kolom DB masih decimal(15,3) karena alasan historis, sehingga tanpa cast
     * Eloquent mengembalikan string seperti "5.000" dan perhitungan di PHP
     * jadi rawan galat pembulatan.
     */
    protected function casts(): array
    {
        return [
            'quantity'          => 'integer',
            'price'             => 'decimal:2',
            'discount_percent'  => 'decimal:2',
            'total'             => 'decimal:2',
        ];
    }

    public function pendingTransaction(): BelongsTo
    {
        return $this->belongsTo(JihansPendingTransaction::class, 'pending_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(JihansProduct::class)->withTrashed();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(JihansUnit::class);
    }
}
