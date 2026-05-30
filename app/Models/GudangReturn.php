<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudangReturn extends Model
{
    protected $table = 'gudang_returns';

    protected $fillable = [
        'return_number', 'from_entity', 'branch_id', 'date',
        'status', 'notes', 'created_by', 'received_by', 'received_at'
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function details(): HasMany    { return $this->hasMany(GudangReturnDetail::class, 'return_id'); }

    public function getFromEntityLabelAttribute(): string
    {
        return match($this->from_entity) {
            'jihans'  => "Jihan's Food",
            'hendhys' => $this->branch ? "Hendhys — {$this->branch->name}" : 'Hendhys Brownies',
            default   => $this->from_entity,
        };
    }
}
