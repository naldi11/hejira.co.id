<?php

namespace App\Models\Hendhys;

use App\Models\Supplier as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends BaseModel
{
    protected $table = 'hendhys_suppliers';
}
