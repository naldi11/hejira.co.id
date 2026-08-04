<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit;
use App\Models\Product;

class JihansGudangStock extends Model
{
    protected $table    = 'jihans_gudang_stock';
    public    $timestamps = false;

    protected $fillable = ['product_id', 'quantity', 'unit_id', 'last_updated'];

    protected function casts(): array
    {
        return [
            'quantity'     => 'integer',
            'last_updated' => 'datetime',
        ];
    }

    /**
     * withTrashed wajib: produk yang sudah dihapus BISA MASIH punya sisa stok
     * (mis. "Saset Sasa" 5 pcs). Tanpa ini relasinya null dan baris stoknya
     * tampil sebagai "-" tanpa nama, sehingga barang fisik yang masih ada di
     * gudang tidak bisa dikenali di layar Owner.
     */
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function unit(): BelongsTo    { return $this->belongsTo(Unit::class); }
}
