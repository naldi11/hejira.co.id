<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit;
use App\Models\Product;

class TransferRequestDetail extends Model
{
    protected $table    = 'gudang_transfer_request_details';
    public    $timestamps = false;

    protected $fillable = [
        'request_id', 'product_id', 'quantity_requested', 'quantity_approved', 'unit_id', 'notes',
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
            'quantity_requested'=> 'integer',
            'quantity_approved' => 'integer',
        ];
    }

    public function request(): BelongsTo  { return $this->belongsTo(TransferRequest::class, 'request_id'); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class)->withTrashed(); }
    public function unit(): BelongsTo     { return $this->belongsTo(Unit::class); }
}
