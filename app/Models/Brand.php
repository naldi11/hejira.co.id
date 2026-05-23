<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $table = 'master_brands';

    protected $fillable = ['name', 'entity_scope', 'visible_gudang', 'visible_jihans', 'visible_hendhys'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
