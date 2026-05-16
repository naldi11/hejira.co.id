<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferOut extends Model
{
    protected $table = 'gudang_transfer_out';

    protected $fillable = [
        'transfer_number', 'request_id', 'to_entity', 'branch_id',
        'date', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function request(): BelongsTo  { return $this->belongsTo(TransferRequest::class, 'request_id'); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function details(): HasMany    { return $this->hasMany(TransferOutDetail::class, 'transfer_id'); }

    public function getToEntityLabelAttribute(): string
    {
        return match($this->to_entity) {
            'jihans'  => "Jihan's Food",
            'hendhys' => $this->branch ? "Hendhys — {$this->branch->name}" : 'Hendhys Brownies',
            default   => $this->to_entity,
        };
    }
}
