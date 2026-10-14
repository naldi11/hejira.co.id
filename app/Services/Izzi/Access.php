<?php

namespace App\Services\Izzi;

use App\Models\{Branch, User};

class Access
{
    public function manage(User $user): void
    {
        abort_if($user->hasRole('owner'), 403);
        abort_unless(($user->hasRole('admin_izzi') && $user->entity === 'izzi')
            || ($user->hasRole('super_admin') && $user->entity === 'all'), 403);
    }

    public function cashier(User $user): void
    {
        abort_unless($user->hasRole('kasir_izzi') && $user->entity === 'izzi' && !$user->hasAnyRole(['owner', 'super_admin']), 403);
    }

    public function branch(User $user, ?int $requested = null, bool $allowInactive = false): Branch
    {
        $global = $user->hasRole('owner') || ($user->hasRole('super_admin') && $user->entity === 'all');
        abort_unless($global || ($user->entity === 'izzi' && $user->hasAnyRole(['kasir_izzi', 'admin_izzi'])), 403);
        $id = $global ? $requested : (session('active_branch_id') ?: $user->branch_id);
        if (!$global && $requested !== null) abort_unless((int) $requested === (int) $id, 403, 'Cabang aktif berubah. Buka kembali halaman.');
        $branch = Branch::where('entity', 'izzi')->when(!$global || !$allowInactive, fn ($q) => $q->where('is_active', true))->when($id, fn ($q) => $q->where('id', $id))->orderByDesc('is_active')->orderBy('id')->first();
        abort_unless($branch && ($global || $id), 403, 'Pilih atau buat cabang Izzi Kebab yang aktif terlebih dahulu.');
        return $branch;
    }
}
