<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class HendhysTransferToBranchPhoto extends Model
{
    public $timestamps = false;
    protected $table = 'hendhys_transfer_to_branch_photos';

    protected $fillable = ['transfer_id', 'path', 'caption', 'uploaded_by', 'created_at'];

    public function transfer(): BelongsTo { return $this->belongsTo(HendhysTransferToBranch::class, 'transfer_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function url(): string { return Storage::url($this->path); }
}
