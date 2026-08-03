<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit;
use App\Models\Product;

class HendhysTransferToBranchDetail extends Model
{
    protected $table = 'hendhys_transfer_to_branch_details';
    public $timestamps = false;

    protected $fillable = [
        'transfer_id', 'product_id', 'quantity', 'received_quantity', 'kondisi', 'unit_id'
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

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(HendhysTransferToBranch::class, 'transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
