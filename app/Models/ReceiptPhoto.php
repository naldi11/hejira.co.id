<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptPhoto extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function receiptConfirmation()
    {
        return $this->belongsTo(ReceiptConfirmation::class);
    }
}
