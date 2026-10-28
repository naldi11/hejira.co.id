<?php

namespace App\Services;

use App\Models\HendhysProduction;
use App\Models\JihansProductionSession;
use App\Models\Product;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionRecording
{
    public function validateProducts(string $entity, array $items): void
    {
        $products = Product::whereIn('id', array_column($items, 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($items as $i => $item) {
            $p = $products->get($item['product_id']);
            $visible = $p && ($p->entity_scope === $entity || $p->entity_scope === 'all' || $p->{'visible_'.$entity});
            if (! $visible || $p->status !== 'active' || $p->source_type !== 'produced' || $p->product_type !== 'INV') {
                throw ValidationException::withMessages(['items' => 'Produksi hanya boleh menggunakan produk persediaan aktif yang diproduksi sendiri untuk entitas ini.']);
            }
            if (isset($item['unit_id']) && (int) $item['unit_id'] !== (int) $p->unit_id) {
                throw ValidationException::withMessages(["items.{$i}.unit_id" => 'Satuan produksi harus sesuai master produk.']);
            }
        }
    }

    public function run(string $entity, array $data, Closure $create)
    {
        $model = $entity === 'hendhys' ? HendhysProduction::class : JihansProductionSession::class;
        if (empty($data['prediction_id']) && empty($data['request_key'])) {
            throw ValidationException::withMessages(['request_key' => 'Nomor permintaan produksi wajib diisi. Muat ulang formulir.']);
        }
        $field = $entity === 'hendhys' ? 'items' : 'details';
        $items = collect($data[$field])->map(fn ($i) => $entity === 'hendhys'
            ? ['product_id' => (int) $i['product_id'], 'unit_id' => (int) $i['unit_id'], 'quantity_produced' => (int) $i['quantity_produced']]
            : ['product_id' => (int) $i['product_id'], 'karyawan_id' => empty($i['karyawan_id']) ? null : (int) $i['karyawan_id'], 'quantity' => (int) $i['quantity']])
            ->sortBy(fn ($i) => sprintf('%020d:%020d', $i['product_id'], $i['karyawan_id'] ?? 0))->values()->all();
        $payload = ['date' => $data['date'], 'notes' => trim($data['notes'] ?? ''), 'prediction_id' => (int) ($data['prediction_id'] ?? 0), $field => $items];
        $hash = hash('sha256', json_encode([$entity, $payload], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($entity, $data, $create, $model, $hash) {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $key = $data['request_key'] ?? null;
            if ($key) {
                $requestHash = hash('sha256', $hash.':'.auth()->id());
                DB::table('production_submissions')->insertOrIgnore(['request_key' => $key, 'entity' => $entity, 'payload_hash' => $requestHash]);
                $saved = DB::table('production_submissions')->where('request_key', $key)->lockForUpdate()->first();
                if (! hash_equals($saved->payload_hash, $requestHash)) {
                    throw ValidationException::withMessages(['request_key' => 'Permintaan produksi ini sudah dipakai untuk data berbeda.']);
                }
                if ($saved->production_id) {
                    return $model::findOrFail($saved->production_id);
                }
            }
            $prediction = empty($data['prediction_id']) ? null : $model::whereKey($data['prediction_id'])->lockForUpdate()->firstOrFail();
            if ($prediction && ($prediction->type !== 'prediksi' || $prediction->overridden_at)) {
                if ($prediction->type === 'aktual' && $prediction->actualization_hash && hash_equals($prediction->actualization_hash, $hash)) {
                    if ($key) {
                        DB::table('production_submissions')->where('request_key', $key)->update(['production_id' => $prediction->id]);
                    }

                    return $prediction;
                }
                throw ValidationException::withMessages(['prediction_id' => 'Prediksi sudah diaktualisasi atau tidak dapat digunakan kembali. Buka riwayat produksi.']);
            }
            $this->validateProducts($entity, $data[$entity === 'hendhys' ? 'items' : 'details']);
            $production = $create($prediction);
            if ($prediction) {
                $production->forceFill(['actualization_hash' => $hash])->save();
            }
            if ($key) {
                DB::table('production_submissions')->where('request_key', $key)->update(['production_id' => $production->id]);
            }

            return $production;
        }, 3);
    }
}
