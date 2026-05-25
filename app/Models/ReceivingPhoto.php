<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReceivingPhoto extends Model
{
    public    $timestamps = false;
    protected $table      = 'gudang_receiving_photos';

    protected $fillable = ['receiving_id', 'path', 'caption', 'uploaded_by', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function receiving(): BelongsTo { return $this->belongsTo(Receiving::class); }
    public function uploader(): BelongsTo  { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function url(): string
    {
        return Storage::url($this->path);
    }
}
