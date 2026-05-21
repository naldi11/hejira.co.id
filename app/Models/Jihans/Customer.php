<?php

namespace App\Models\Jihans;

use App\Models\Customer as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends BaseModel
{
    protected $table = 'jihans_customers';
}
