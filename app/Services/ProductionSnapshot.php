<?php

namespace App\Services;

class ProductionSnapshot
{
    public static function capture($production, string $entity): array
    {
        $production->load('details.product.unit', 'creator', $entity === 'hendhys' ? 'details.unit' : 'details.karyawan');
        return [
            'date' => $production->date->format('Y-m-d'),
            'notes' => $production->notes,
            'creator' => $production->creator?->name,
            'items' => $production->details->map(fn ($detail) => [
                'product_id' => $detail->product_id,
                'product' => $detail->product?->name ?? 'Produk tidak tersedia',
                'quantity' => (int) ($entity === 'hendhys' ? $detail->quantity_produced : $detail->quantity),
                'unit' => ($entity === 'hendhys' ? $detail->unit?->abbreviation : $detail->product?->unit?->abbreviation) ?? '-',
                'employee' => $entity === 'jihans' ? $detail->karyawan?->name : null,
            ])->values()->all(),
        ];
    }
}
