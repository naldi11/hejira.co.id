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
        'tb_rate', 'ts_rate', 'tk_rate', 'tc_rate', 'kribab_rate',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'tb_qty'      => 'integer',
            'ts_qty'      => 'integer',
            'tk_qty'      => 'integer',
            'tc_qty'      => 'integer',
            'kribab_qty'  => 'integer',
            'tb_rate'     => 'decimal:2',
            'ts_rate'     => 'decimal:2',
            'tk_rate'     => 'decimal:2',
            'tc_rate'     => 'decimal:2',
            'kribab_rate' => 'decimal:2',
            'total_amount'=> 'decimal:2',
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
