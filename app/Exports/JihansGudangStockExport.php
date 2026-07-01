<?php

namespace App\Exports;

use App\Models\JihansGudangStock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class JihansGudangStockExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return JihansGudangStock::with('product', 'unit')->get();
    }

    public function headings(): array
    {
        return [
            'Kode Produk',
            'Nama Produk',
            'Kategori',
            'Stok Saat Ini',
            'Satuan',
            'Update Terakhir'
        ];
    }

    public function map($stock): array
    {
        return [
            $stock->product->code,
            $stock->product->name,
            $stock->product->category->name ?? '-',
            $stock->quantity,
            $stock->unit->name,
            $stock->last_updated->format('d/m/Y H:i')
        ];
    }
}
