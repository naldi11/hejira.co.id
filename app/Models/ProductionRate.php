<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRate extends Model
{
    protected $table = 'master_production_rates';

    protected $fillable = [
        'entity_scope',
        'tb_rate', 'ts_rate', 'tk_rate', 'tc_rate', 'kribab_rate',
        'tb_product_id', 'ts_product_id', 'tk_product_id', 'tc_product_id', 'kribab_product_id',
        'notes', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tb_rate'     => 'decimal:2',
            'ts_rate'     => 'decimal:2',
            'tk_rate'     => 'decimal:2',
            'tc_rate'     => 'decimal:2',
            'kribab_rate' => 'decimal:2',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tbProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'tb_product_id');
    }

    public function tsProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ts_product_id');
    }

    public function tkProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'tk_product_id');
    }

    public function tcProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'tc_product_id');
    }

    public function kribabProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kribab_product_id');
    }
}
