<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JihansTortillaSession extends Model
{
    protected $table = 'jihans_tortilla_sessions';

    protected $fillable = [
        'session_number', 'type', 'overridden_at', 'date', 'notes', 'created_by',
        'tb_product_id', 'ts_product_id', 'tk_product_id', 'tc_product_id', 'kribab_product_id',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'overridden_at' => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(JihansTortillaSessionDetail::class, 'session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPrediksi(): bool
    {
        return $this->type === 'prediksi';
    }

    public function isOverridden(): bool
    {
        return $this->overridden_at !== null;
    }
}
