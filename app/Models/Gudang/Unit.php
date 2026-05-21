<?php

namespace App\Models\Gudang;

use App\Models\Unit as BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends BaseModel
{
    protected $table = 'gudang_units';

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
}
