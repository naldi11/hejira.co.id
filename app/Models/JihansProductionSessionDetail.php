<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JihansProductionSessionDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function session()
    {
        return $this->belongsTo(JihansProductionSession::class, 'session_id');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id'); // Using Karyawan model 
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
