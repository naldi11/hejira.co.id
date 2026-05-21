<?php

namespace App\Models\Gudang;

use App\Models\Customer as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends BaseModel
{
    protected $table = 'gudang_customers';
}
