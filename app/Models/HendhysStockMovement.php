<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hendhys\Unit as HendhysUnit;
use App\Models\Hendhys\Product as HendhysProduct;

class HendhysStockMovement extends Model
{
    protected $table = 'hendhys_stock_movements';

    protected $fillable = [
        'branch_id', 'product_id', 'type', 'quantity', 'quantity_before', 'quantity_after', 
        'source', 'reference_id', 'notes', 'created_by'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(HendhysProduct::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
