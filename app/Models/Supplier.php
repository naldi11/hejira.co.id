<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'master_suppliers';

    protected $fillable = ['code', 'name', 'contact_person', 'phone', 'email', 'address', 'notes', 'is_active', 'entity_scope'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
