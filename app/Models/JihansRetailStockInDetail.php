<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product as JihansProduct;
use App\Models\Unit;

class JihansRetailStockInDetail extends Model
{
    protected $table    = 'jihans_retail_stock_in_details';
    public    $timestamps = false;

    protected $fillable = ['stock_in_id', 'product_id', 'quantity', 'unit_id', 'hpp_price', 'notes'];

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
            'hpp_price'         => 'decimal:2',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(JihansProduct::class); }
    public function unit(): BelongsTo    { return $this->belongsTo(Unit::class); }
}
