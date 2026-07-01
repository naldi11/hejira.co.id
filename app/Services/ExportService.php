<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JihansGudangStockExport;

class ExportService
{
    public function exportJihansGudangStock(string $format = 'xlsx')
    {
        $filename = 'stok-jihans-gudang-' . now()->format('YmdHis') . '.' . $format;
        return Excel::download(new JihansGudangStockExport, $filename);
    }

    // Tambahkan method export lainnya di sini seiring kebutuhan
}
