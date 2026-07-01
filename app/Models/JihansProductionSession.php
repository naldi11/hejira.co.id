<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JihansProductionSession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'date' => 'date',
        'overridden_at' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(JihansProductionSessionDetail::class, 'session_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by'); // Using User::class assuming it's the standard auth model
    }

    public function isPrediksi()
    {
        return $this->type === 'prediksi';
    }
}
