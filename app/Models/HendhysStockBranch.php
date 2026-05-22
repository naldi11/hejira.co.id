<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hendhys\Unit as HendhysUnit;
use App\Models\Product as HendhysProduct;

class HendhysStockBranch extends Model
{
    protected $table    = 'hendhys_stock_branch';
    public    $timestamps = false;

    protected $fillable = ['branch_id', 'product_id', 'quantity', 'unit_id', 'last_updated'];

    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
    public function product(): BelongsTo { return $this->belongsTo(HendhysProduct::class); }
    public function unit(): BelongsTo    { return $this->belongsTo(HendhysUnit::class); }
}
