<?php

namespace App\Models\Gudang;

use App\Models\Supplier as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends BaseModel
{
    protected $table = 'gudang_suppliers';
}
