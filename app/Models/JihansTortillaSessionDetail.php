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
        'hitam_besar_qty', 'hitam_sedang_qty', 'hitam_mini_qty',
        'albaik_besar_qty', 'albaik_sedang_qty', 'albaik_mini_qty',
        'regular_besar_qty', 'regular_sedang_qty', 'regular_mini_qty',
        'lentur_besar_qty', 'lentur_sedang_qty', 'lentur_mini_qty',
    ];

    protected function casts(): array
    {
        return [
            'tb_qty'     => 'integer',
            'ts_qty'     => 'integer',
            'tk_qty'     => 'integer',
            'tc_qty'     => 'integer',
            'kribab_qty' => 'integer',
            'hitam_besar_qty' => 'integer', 'hitam_sedang_qty' => 'integer', 'hitam_mini_qty' => 'integer',
            'albaik_besar_qty' => 'integer', 'albaik_sedang_qty' => 'integer', 'albaik_mini_qty' => 'integer',
            'regular_besar_qty' => 'integer', 'regular_sedang_qty' => 'integer', 'regular_mini_qty' => 'integer',
            'lentur_besar_qty' => 'integer', 'lentur_sedang_qty' => 'integer', 'lentur_mini_qty' => 'integer',
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
