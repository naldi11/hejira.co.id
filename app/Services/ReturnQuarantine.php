<?php

namespace App\Services;

use App\Models\{Branch, Product, Unit, User};
use Illuminate\Support\Facades\DB;

class ReturnQuarantine
{
    public static function condition(?string $condition): string
    {
        $value = mb_strtolower(trim($condition ?? ''));
        return match ($value) {
            'baik', 'bagus', 'bagus (siap jual)' => 'good',
            'basi', 'kadaluarsa', 'kadaluwarsa', 'kedaluwarsa', 'expired' => 'expired',
            'rusak', 'rusak (defect)' => 'damaged',
            default => 'other',
        };
    }

    public static function itemSnapshot(int $productId, int $unitId): array
    {
        $p = Product::withTrashed()->findOrFail($productId);
        $u = Unit::findOrFail($unitId);
        abort_unless((int) $p->unit_id === $unitId, 422, 'Satuan retur harus sesuai satuan produk.');
        return ['product_name' => $p->name, 'product_code' => $p->code, 'unit_name' => $u->abbreviation];
    }

    public static function origin(User $user): array
    {
        $branch = Branch::find($user->branch_id);
        if (in_array($user->entity, ['hendhys', 'jihans'], true)) abort_unless($branch && $branch->is_active && $branch->entity === $user->entity, 422, 'Akun harus ditempatkan di cabang aktif yang sesuai.');
        return ['origin_name' => $branch?->name ?? 'Pusat tanpa cabang', 'sender_name' => $user->name];
    }

    public function record(string $source, $return, bool $legacy = false): void
    {
        $return->loadMissing(['branch', 'creator', 'receiver', 'details.product', 'details.unit']);
        foreach ($return->details as $detail) {
            $qty = $source === 'gudang' ? $detail->received_quantity : $detail->quantity;
            $kind = self::condition($detail->condition);
            if ($qty <= 0 || ($source === 'gudang' && $kind === 'good')) continue;
            // All branch-to-production returns remain quarantined, including unclear historical conditions.
            if ($kind === 'good') $kind = 'other';
            DB::table('return_quarantine_entries')->insertOrIgnore([
                'source_type' => $source, 'return_id' => $return->id, 'detail_id' => $detail->id, 'return_number' => $return->return_number,
                'destination_key' => $source === 'gudang' ? 'gudang' : 'hendhys-pusat',
                'origin_entity' => $source === 'gudang' ? $return->from_entity : $return->branch?->entity,
                'origin_branch_id' => $return->branch_id, 'origin_name' => $return->origin_name ?? $return->branch?->name ?? 'Cabang belum tercatat',
                'product_id' => $detail->product_id, 'unit_id' => $detail->unit_id,
                'product_name' => $detail->product_name ?? $detail->product?->name ?? 'Produk tidak tersedia',
                'product_code' => $detail->product_code ?? $detail->product?->code, 'unit_name' => $detail->unit_name ?? $detail->unit?->abbreviation ?? '-',
                'quantity' => $qty, 'condition' => $kind, 'sent_condition' => $source === 'gudang' ? $detail->sent_condition : $detail->condition,
                'received_condition' => $detail->condition,
                'reason' => trim(implode(' · ', array_filter([$return->notes, $detail->notes]))) ?: null,
                'receiving_notes' => $source === 'gudang' ? $detail->receiving_notes : null,
                'return_date' => $source === 'gudang' ? $return->date->toDateString() : $return->date,
                'sent_by' => $return->created_by, 'sent_by_name' => $return->sender_name ?? $return->creator?->name,
                'received_by' => $return->received_by, 'received_by_name' => $return->receiver?->name,
                'received_at' => $return->received_at, 'recorded_at' => now(), 'legacy' => $legacy,
            ]);
        }
    }

    public function visible(User $user)
    {
        $q = DB::table('return_quarantine_entries');
        if ($user->hasRole('owner')) return $q;
        if ($user->hasRole('super_admin')) return match ($user->entity) {
            'all' => $q, 'gudang' => $q->where('destination_key', 'gudang'), 'hendhys' => $q->where(fn ($w) => $w->where('destination_key', 'like', 'hendhys-%')->orWhere('origin_entity', 'hendhys')), 'jihans' => $q->where(fn ($w) => $w->where('origin_entity', 'jihans')->orWhere('destination_key', 'jihans-retail')), default => $q->whereRaw('1 = 0'),
        };
        $branch = Branch::find(session('active_branch_id') ?: $user->branch_id);
        if (!$branch || !$branch->is_active || $branch->entity !== $user->entity) return $q->whereRaw('1 = 0');
        if ($user->hasRole('admin_hendhys') && $branch->type === 'pusat') return $q->where(fn ($w) => $w->where('destination_key', 'hendhys-pusat')->orWhere(fn ($own) => $own->where('origin_entity', 'hendhys')->where('origin_branch_id', $branch->id)));
        $destination = $user->entity === 'jihans' ? 'jihans-retail' : ($branch->type === 'pusat' ? 'hendhys-pusat' : 'hendhys-branch-'.$branch->id);
        return $q->where(fn ($w) => $w->where('destination_key', $destination)->orWhere(fn ($own) => $own->where('origin_entity', $user->entity)->where('origin_branch_id', $branch->id)));
    }

    public function recordTransferReceipt($document, $receipt, string $entity, User $user): void
    {
        $warehouse = $document instanceof \App\Models\TransferOut;
        $branch = $document->branch;
        $destination = $entity === 'jihans' ? 'jihans-retail' : ($branch?->type === 'pusat' || !$branch ? 'hendhys-pusat' : 'hendhys-branch-'.$branch->id);
        foreach ($receipt->details()->where('condition', 'rusak')->get() as $item) {
            $detail = $document->details->firstWhere('product_id', $item->product_id);
            DB::table('return_quarantine_entries')->insertOrIgnore([
                'source_type' => $warehouse ? 'warehouse_receipt' : 'centre_receipt', 'return_id' => $document->id,
                'detail_id' => $item->id, 'return_number' => $document->transfer_number, 'destination_key' => $destination,
                'origin_entity' => $warehouse ? 'gudang' : 'hendhys', 'origin_name' => $warehouse ? 'Gudang Utama' : 'Hendhys Pusat',
                'product_id' => $item->product_id, 'unit_id' => $detail->unit_id, 'product_name' => $detail->product?->name ?? 'Produk tidak tersedia',
                'product_code' => $detail->product?->code, 'unit_name' => $detail->unit?->abbreviation ?? '-',
                'quantity' => $item->actual_qty, 'condition' => 'damaged', 'received_condition' => 'rusak',
                'reason' => $document->receive_notes, 'return_date' => now()->toDateString(), 'sent_by' => $document->created_by,
                'received_by' => $user->id, 'received_by_name' => $user->name, 'received_at' => now(), 'recorded_at' => now(),
            ]);
        }
    }
}
