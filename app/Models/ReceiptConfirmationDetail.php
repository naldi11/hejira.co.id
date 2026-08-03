<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptConfirmationDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'expired_date' => 'date',
        // expected_qty/actual_qty adalah KUANTITAS barang (bukan nilai uang),
        // walau kolomnya terlanjur decimal(15,2) di migrasi.
        'expected_qty' => 'integer',
        'actual_qty'   => 'integer',
    ];

    public function receiptConfirmation()
    {
        return $this->belongsTo(ReceiptConfirmation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
