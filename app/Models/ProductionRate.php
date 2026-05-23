<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRate extends Model
{
    protected $table = 'master_production_rates';

    protected $fillable = [
        'entity_scope', 'tb_rate', 'ts_rate', 'tk_rate',
        'tc_rate', 'kribab_rate', 'notes', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tb_rate'    => 'decimal:2',
            'ts_rate'    => 'decimal:2',
            'tk_rate'    => 'decimal:2',
            'tc_rate'    => 'decimal:2',
            'kribab_rate'=> 'decimal:2',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
