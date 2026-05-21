<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hendhys\Unit as HendhysUnit;
use App\Models\Hendhys\Product as HendhysProduct;
class HendhysReturnDetail extends Model
{
    protected $table = 'hendhys_return_details';
    public $timestamps = false;

    protected $fillable = [
        'return_id', 'product_id', 'quantity', 'unit_id'
    ];

    public function returnFromBranch(): BelongsTo
    {
        return $this->belongsTo(HendhysReturnFromBranch::class, 'return_id');
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
