<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Unit as HendhysUnit;
use App\Models\Product as HendhysProduct;

class HendhysBranchRequestDetail extends Model
{
    protected $table = 'hendhys_branch_request_details';
    public $timestamps = false;

    protected $fillable = [
        'request_id', 'product_id', 'quantity_requested', 'quantity_approved', 'unit_id'
    ];

    public function branchRequest(): BelongsTo
    {
        return $this->belongsTo(HendhysBranchRequest::class, 'request_id');
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
