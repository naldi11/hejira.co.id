<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product as HendhysProduct;
use App\Models\Unit as HendhysUnit;

class HendhysTransactionDetail extends Model
{
    protected $table = 'hendhys_transaction_details';

    protected $fillable = [
        'transaction_id', 'product_id', 'product_name', 'quantity', 'unit_id',
        'price', 'discount_amount', 'total'
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
            'discount_amount'   => 'decimal:2',
            'total'             => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(HendhysTransaction::class, 'transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(HendhysProduct::class)->withTrashed();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(HendhysUnit::class);
    }
}
