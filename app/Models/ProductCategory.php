<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $table = 'master_product_categories';

    protected $fillable = ['name', 'entity'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
