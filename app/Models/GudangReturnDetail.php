<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudangReturnDetail extends Model
{
    protected $table = 'gudang_return_details';
    public $timestamps = false;

    protected $fillable = [
        'return_id', 'product_id', 'quantity', 'unit_id',
        'received_quantity', 'condition', 'notes'
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
            'received_quantity' => 'integer',
        ];
    }

    public function return(): BelongsTo  { return $this->belongsTo(GudangReturn::class, 'return_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id'); }
    public function unit(): BelongsTo    { return $this->belongsTo(Unit::class, 'unit_id'); }
}
