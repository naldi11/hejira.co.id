<?php

namespace App\Models\Jihans;

use App\Models\Brand as BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends BaseModel
{
    protected $table = 'jihans_brands';

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
