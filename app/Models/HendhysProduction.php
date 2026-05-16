<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HendhysProduction extends Model
{
    protected $table = 'hendhys_productions';

    protected $fillable = [
        'production_number', 'date', 'total_items', 'notes', 'created_by'
    ];

    public function details(): HasMany
    {
        return $this->hasMany(HendhysProductionDetail::class, 'production_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
