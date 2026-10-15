<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockMovementDocuments
{
    /** Fetch document numbers once per document type, rather than once per row. */
    public function load(Collection $movements): void
    {
        foreach ($movements as $movement) $movement->setAttribute('loaded_document_number', null);
        $transfers = $movements->filter(fn ($m) => $m->branch_transfer_id);
        if ($transfers->isNotEmpty()) {
            $numbers = DB::table('branch_stock_transfers')->whereIn('id', $transfers->pluck('branch_transfer_id'))->pluck('number', 'id');
            foreach ($transfers as $m) $m->setAttribute('loaded_document_number', $numbers[$m->branch_transfer_id] ?? null);
        }
        $materials = $movements->filter(fn ($m) => $m->production_material_id);
        if ($materials->isNotEmpty()) {
            $numbers = DB::table('production_material_transactions')->whereIn('id', $materials->pluck('production_material_id'))->pluck('number', 'id');
            foreach ($materials as $m) $m->setAttribute('loaded_document_number', $numbers[$m->production_material_id] ?? null);
        }
        $ordinary = $movements->filter(fn ($m) => !$m->production_material_id && !$m->branch_transfer_id && $m->reference_id);
        foreach ([
            ['gudang_transfer_out', 'transfer_number', ['transfer_out']],
            ['gudang_receivings', 'grn_number', ['purchase_receiving', 'receiving', 'receiving_edit']],
            ['gudang_returns', 'return_number', ['return_receiving', 'return_receive']],
        ] as [$table, $column, $sources]) {
            $rows = $ordinary->whereIn('source', $sources)->reject(fn ($m) => str_starts_with($m->notes ?? '', 'Selisih penerimaan dari '));
            if ($rows->isEmpty()) continue;
            $numbers = DB::table($table)->whereIn('id', $rows->pluck('reference_id'))->pluck($column, 'id');
            foreach ($rows as $m) $m->setAttribute('loaded_document_number', $numbers[$m->reference_id] ?? null);
        }
        $shortfalls = $ordinary->whereIn('source', ['return_receiving', 'return_receive'])
            ->filter(fn ($m) => str_starts_with($m->notes ?? '', 'Selisih penerimaan dari '));
        if ($shortfalls->isNotEmpty()) {
            $numbers = DB::table('gudang_transfer_out')->whereIn('id', $shortfalls->pluck('reference_id'))->pluck('transfer_number', 'id');
            foreach ($shortfalls as $m) {
                $number = $numbers[$m->reference_id] ?? null;
                if ($number === substr($m->notes, strlen('Selisih penerimaan dari '))) $m->setAttribute('loaded_document_number', $number);
            }
        }
    }
}
