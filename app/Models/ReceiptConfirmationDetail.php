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
