<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JihansRetailStockIn extends Model
{
    protected $table    = 'jihans_retail_stock_in';
    public    $timestamps = false;

    protected $fillable = ['stock_in_number', 'transfer_out_id', 'date', 'notes', 'created_by'];

    protected function casts(): array { return ['date' => 'date']; }

    public function transfer(): BelongsTo { return $this->belongsTo(TransferOut::class, 'transfer_out_id'); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function details(): HasMany    { return $this->hasMany(JihansRetailStockInDetail::class, 'stock_in_id'); }
}
