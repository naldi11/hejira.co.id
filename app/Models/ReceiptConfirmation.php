<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptConfirmation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function receiptable()
    {
        return $this->morphTo();
    }

    public function details()
    {
        return $this->hasMany(ReceiptConfirmationDetail::class);
    }

    public function photos()
    {
        return $this->hasMany(ReceiptPhoto::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
