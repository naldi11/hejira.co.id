<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $table = 'master_customers';

    protected $fillable = ['code', 'name', 'type', 'phone', 'email', 'address', 'notes', 'is_active', 'entity_scope'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
