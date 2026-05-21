<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $table = 'master_units';

    protected $fillable = ['name', 'abbreviation', 'entity_scope'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
