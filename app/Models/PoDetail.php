<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit;
use App\Models\Product;

class PoDetail extends Model
{
    protected $table    = 'gudang_po_details';
    public    $timestamps = false;

    protected $fillable = [
        'po_id', 'product_id', 'quantity_ordered', 'quantity_received',
        'unit_id', 'price', 'total', 'notes',
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
            'quantity_ordered'  => 'integer',
            'quantity_received' => 'integer',
            'price'             => 'decimal:2',
            'total'             => 'decimal:2',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function unit(): BelongsTo    { return $this->belongsTo(Unit::class); }
    public function po(): BelongsTo      { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
}
