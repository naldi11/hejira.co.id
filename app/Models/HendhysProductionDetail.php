<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hendhys\Unit as HendhysUnit;
use App\Models\Hendhys\Product as HendhysProduct;

class HendhysProductionDetail extends Model
{
    protected $table = 'hendhys_production_details';
    public $timestamps = false;

    protected $fillable = [
        'production_id', 'product_id', 'quantity_produced', 'unit_id'
    ];

    public function production(): BelongsTo
    {
        return $this->belongsTo(HendhysProduction::class, 'production_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(HendhysProduct::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(HendhysUnit::class);
    }
}
