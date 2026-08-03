<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $table = 'master_branches';

    // 'entity' wajib ada di sini: BranchSelectionController & dashboard Owner
    // menyaring cabang lewat kolom ini. Tanpa fillable, Branch::create()/update()
    // membuangnya diam-diam dan cabang baru tersimpan dengan entity NULL.
    protected $fillable = ['code', 'name', 'type', 'entity', 'address', 'phone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
