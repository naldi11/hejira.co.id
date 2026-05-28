<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JihansProductionConfig extends Model
{
    protected $table = 'jihans_production_config';

    protected $fillable = [
        'tb_product_id',
        'ts_product_id',
        'tk_product_id',
        'tc_product_id',
        'kribab_product_id',
        'updated_by',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([]);
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
