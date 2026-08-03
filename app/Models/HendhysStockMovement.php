<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit as HendhysUnit;
use App\Models\Product as HendhysProduct;

class HendhysStockMovement extends Model
{
    protected $table = 'hendhys_stock_movements';

    protected $fillable = [
        'branch_id', 'product_id', 'type', 'quantity', 'quantity_before', 'quantity_after', 
        'source', 'reference_id', 'notes', 'created_by'
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
            'quantity_before'   => 'integer',
            'quantity_after'    => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(HendhysProduct::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
