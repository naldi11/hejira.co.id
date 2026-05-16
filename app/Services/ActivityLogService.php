<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public function log(
        string  $action,
        string  $module,
        string  $description,
        ?Model  $model = null,
        ?array  $oldData = null,
        ?array  $newData = null
    ): void {
        $user = auth()->user();

        DB::table('master_activity_logs')->insert([
            'user_id'     => $user?->id,
            'user_name'   => $user?->name ?? 'System',
            'user_role'   => $user?->getRoleNames()->first() ?? '-',
            'action'      => $action,
            'module'      => $module,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->id,
            'description' => $description,
            'old_data'    => $oldData ? json_encode($oldData) : null,
            'new_data'    => $newData ? json_encode($newData) : null,
            'ip_address'  => Request::ip(),
            'created_at'  => now(),
        ]);
    }
}
