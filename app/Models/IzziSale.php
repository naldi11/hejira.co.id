<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IzziSale extends Model
{
    protected $table = 'izzi_sales';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['created_at' => 'datetime'];
}
