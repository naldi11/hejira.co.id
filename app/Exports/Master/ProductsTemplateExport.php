<?php

namespace App\Exports\Master;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Memberi satu contoh baris agar user paham format pengisian
        return [
            ['Frozen Food', 'Pak', 'Jihans', '8991234', 'Ayam Fillet 1Kg', 40000, 50000, 10, 'none', '11', 'INV', 'all'],
            ['Tortilla', 'Pak', '', '8994567', 'Tortilla Sedang', 12000, 15000, 20, 'include', '11', 'INV', 'jihans'],
            ['Bahan Baku', 'Kg', '', '', 'Tepung Terigu Segitiga Biru', 10000, 11000, 50, 'none', '11', 'INV', 'all'],
        ];
    }

    public function headings(): array
    {
        return [
            'Kategori', 'Satuan', 'Brand', 'Barcode', 'Nama Produk', 
            'HPP', 'Harga Jual', 'Stok Min', 'Tipe PPN', 'Rate PPN', 'Tipe Produk', 'Entitas Scope'
        ];
    }
}
