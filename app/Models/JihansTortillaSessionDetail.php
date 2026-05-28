<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JihansTortillaSessionDetail extends Model
{
    protected $table = 'jihans_tortilla_session_details';

    protected $fillable = [
        'session_id', 'karyawan_id',
        'tb_qty', 'ts_qty', 'tk_qty', 'tc_qty', 'kribab_qty',
    ];

    protected function casts(): array
    {
        return [
            'tb_qty'     => 'integer',
            'ts_qty'     => 'integer',
            'tk_qty'     => 'integer',
            'tc_qty'     => 'integer',
            'kribab_qty' => 'integer',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(JihansTortillaSession::class, 'session_id');
    }
}
