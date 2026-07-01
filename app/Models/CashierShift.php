<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierShift extends Model
{
    use HasFactory;

    protected $table = 'master_cashier_shifts';

    protected $fillable = [
        'user_id',
        'branch_id',
        'entity',
        'status',
        'opened_at',
        'closed_at',
        'starting_cash',
        'expected_cash',
        'actual_cash',
        'discrepancy',
        'note',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'starting_cash' => 'integer',
        'expected_cash' => 'integer',
        'actual_cash' => 'integer',
        'discrepancy' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
