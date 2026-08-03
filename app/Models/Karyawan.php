<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catatan: relasi tortillaDetails() dihapus karena menunjuk ke kelas
 * JihansTortillaSessionDetail yang tidak pernah ada (sisa rename ke
 * JihansProductionSessionDetail). Memanggilnya akan fatal error.
 * Relasi ke sesi produksi sudah tersedia dari sisi
 * JihansProductionSessionDetail::karyawan().
 */
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
}
