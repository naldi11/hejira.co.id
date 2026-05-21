<?php

namespace App\Models\Hendhys;

use App\Models\ProductCategory as BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends BaseModel
{
    protected $table = 'hendhys_product_categories';

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
