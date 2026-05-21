<?php

namespace App\Models\Gudang;

use App\Models\ProductCategory as BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends BaseModel
{
    protected $table = 'gudang_product_categories';

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
