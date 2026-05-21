<?php

namespace App\Models\Gudang;

use App\Models\Brand as BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends BaseModel
{
    protected $table = 'gudang_brands';

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
