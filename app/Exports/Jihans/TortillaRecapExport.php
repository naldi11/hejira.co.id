<?php

namespace App\Exports\Jihans;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TortillaRecapExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(protected Collection $recap, protected string $period) {}

    public function collection()
    {
        return $this->recap;
    }

    public function headings(): array
    {
        return [
            ['REKAP PRODUKSI KARYAWAN TORTILLA'],
            ['Periode: ' . $this->period],
            [''],
            ['Nama Karyawan', 'Total Hadir', 'TB', 'TS', 'TK', 'TC', 'KRIBAB']
        ];
    }

    public function map($item): array
    {
        return [
            $item->karyawan->name,
            $item->hadir_count . ' hari',
            $item->total_tb,
            $item->total_ts,
            $item->total_tk,
            $item->total_tc,
            $item->total_kribab,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true]],
            4 => ['font' => ['bold' => true]],
        ];
    }
}
