<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HendhysProduction extends Model
{
    protected $table = 'hendhys_productions';

    protected $fillable = [
        'production_number', 'type', 'date', 'total_items', 'notes', 'created_by', 'overridden_at'
    ];

    protected $casts = [
        'overridden_at' => 'datetime',
        'date' => 'date',
    ];

    public function isPrediksi(): bool
    {
        return $this->type === 'prediksi';
    }

    public function details(): HasMany
    {
        return $this->hasMany(HendhysProductionDetail::class, 'production_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
