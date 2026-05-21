<?php

namespace App\Models\Jihans;

use App\Models\Supplier as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends BaseModel
{
    protected $table = 'jihans_suppliers';
}
