<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** Call inside a DB transaction. All sale writers and shift closing lock the cashier first. */
class SaleShiftGuard
{
    public function active(User $user): CashierShift
    {
        User::whereKey($user->id)->lockForUpdate()->firstOrFail();
        $shift = CashierShift::where('user_id', $user->id)->where('entity', $user->entity)
            ->where('status', 'open')->lockForUpdate()->first();
        if (! $shift || (int) $shift->branch_id !== (int) $user->branch_id) {
            throw ValidationException::withMessages(['shift' => 'Buka laci kasir cabang aktif terlebih dahulu.']);
        }

        return $shift;
    }

    public function sale(Model $snapshot, string $entity): Model
    {
        User::whereKey($snapshot->created_by)->lockForUpdate()->firstOrFail();
        $shift = CashierShift::where('user_id', $snapshot->created_by)->where('entity', $entity)
            ->when($snapshot->shift_id, fn ($q) => $q->whereKey($snapshot->shift_id))
            ->where('status', 'open')->lockForUpdate()->latest('id')->first();
        $sale = $snapshot->newQuery()->whereKey($snapshot->id)->lockForUpdate()->firstOrFail();
        $branchId = $shift?->branch_id;
        if ($entity === 'hendhys' && $branchId && Branch::find($branchId)?->type === 'pusat') {
            $branchId = null;
        }
        if (! $shift || ($sale->shift_id && (int) $sale->shift_id !== (int) $shift->id)
            || (! $sale->shift_id && $sale->created_at < $shift->opened_at)
            || ($entity === 'hendhys' && (int) $sale->branch_id !== (int) $branchId)) {
            throw ValidationException::withMessages(['transaction' => 'Transaksi ini berasal dari shift yang sudah ditutup sehingga tidak bisa diubah atau dibatalkan. Gunakan mekanisme retur.']);
        }
        if ($sale->status === 'cancelled') {
            throw ValidationException::withMessages(['transaction' => 'Transaksi ini sudah dibatalkan sebelumnya.']);
        }

        return $sale;
    }
}
