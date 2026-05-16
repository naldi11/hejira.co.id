<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HendhysProductionDetail extends Model
{
    protected $table = 'hendhys_production_details';

    protected $fillable = [
        'production_id', 'product_id', 'quantity_produced', 'unit_id'
    ];

    public function production(): BelongsTo
    {
        return $this->belongsTo(HendhysProduction::class, 'production_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
