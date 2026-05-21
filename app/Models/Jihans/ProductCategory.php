<?php

namespace App\Models\Jihans;

use App\Models\ProductCategory as BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends BaseModel
{
    protected $table = 'jihans_product_categories';

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
