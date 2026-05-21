<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GudangStockExport;

class ExportService
{
    public function exportGudangStock(string $format = 'xlsx')
    {
        $filename = 'stok-gudang-' . now()->format('YmdHis') . '.' . $format;
        return Excel::download(new GudangStockExport, $filename);
    }

    // Tambahkan method export lainnya di sini seiring kebutuhan
}
