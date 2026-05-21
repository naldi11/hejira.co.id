<?php

namespace App\Models\Hendhys;

use App\Models\Customer as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends BaseModel
{
    protected $table = 'hendhys_customers';
}
