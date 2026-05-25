<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'master_products';

    protected $fillable = [
        'code', 'barcode', 'name', 'category_id', 'unit_id', 'brand_id',
        'rack', 'jenis', 'hpp', 'selling_price', 'stock_min',
        'ppn_type', 'ppn_rate', 'product_type', 'source_type', 'entity_scope',
        'visible_gudang', 'visible_jihans', 'visible_hendhys',
        'status', 'notes', 'image', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'hpp'             => 'decimal:2',
            'selling_price'   => 'decimal:2',
            'ppn_rate'        => 'decimal:2',
            'visible_gudang'  => 'boolean',
            'visible_jihans'  => 'boolean',
            'visible_hendhys' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tieredPrices()
    {
        return $this->hasMany(MasterProductTieredPrice::class)->orderBy('min_qty', 'desc');
    }

    // Scopes untuk visibilitas entitas
    public function scopeVisibleInGudang($query)
    {
        return $query->where(function ($q) {
            $q->where('entity_scope', 'gudang')
              ->orWhere('entity_scope', 'all')
              ->orWhere('visible_gudang', true);
        });
    }

    public function scopeVisibleInJihans($query)
    {
        return $query->where(function ($q) {
            $q->where('entity_scope', 'jihans')
              ->orWhere('entity_scope', 'all')
              ->orWhere('visible_jihans', true);
        });
    }

    public function scopeVisibleInHendhys($query)
    {
        return $query->where(function ($q) {
            $q->where('entity_scope', 'hendhys')
              ->orWhere('entity_scope', 'all')
              ->orWhere('visible_hendhys', true);
        });
    }
}
