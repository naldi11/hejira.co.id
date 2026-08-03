<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterProductTieredPrice extends Model
{
    protected $fillable = ['product_id', 'min_qty', 'price'];

    /**
     * Kuantitas stok WAJIB integer; hanya nilai uang yang desimal.
     * Kolom DB masih decimal(15,3) karena alasan historis, sehingga tanpa cast
     * Eloquent mengembalikan string seperti "5.000" dan perhitungan di PHP
     * jadi rawan galat pembulatan.
     */
    protected function casts(): array
    {
        return [
            'min_qty'           => 'integer',
            'price'             => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
