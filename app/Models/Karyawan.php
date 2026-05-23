<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    use SoftDeletes;

    protected $table = 'master_karyawan';

    protected $fillable = [
        'entity_scope', 'name', 'phone', 'address', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tortillaDetails(): HasMany
    {
        return $this->hasMany(JihansTortillaSessionDetail::class, 'karyawan_id');
    }
}
