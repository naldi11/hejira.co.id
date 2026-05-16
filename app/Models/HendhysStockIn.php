<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HendhysStockIn extends Model
{
    protected $table    = 'hendhys_stock_in';
    public    $timestamps = false;

    protected $fillable = ['stock_in_number', 'transfer_out_id', 'branch_id', 'date', 'notes', 'created_by'];

    protected function casts(): array { return ['date' => 'date']; }

    public function transfer(): BelongsTo { return $this->belongsTo(TransferOut::class, 'transfer_out_id'); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
}
