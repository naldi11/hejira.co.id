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
            ['Nama Karyawan', 'Total Hadir', 'TB', 'TS', 'TK', 'TC', 'KRIBAB', 
             'HTM BSR', 'HTM SDG', 'HTM MNI', 
             'ALBK BSR', 'ALBK SDG', 'ALBK MNI', 
             'RGLR BSR', 'RGLR SDG', 'RGLR MNI', 
             'LNTR BSR', 'LNTR SDG', 'LNTR MNI']
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
            $item->total_hitam_besar,
            $item->total_hitam_sedang,
            $item->total_hitam_mini,
            $item->total_albaik_besar,
            $item->total_albaik_sedang,
            $item->total_albaik_mini,
            $item->total_regular_besar,
            $item->total_regular_sedang,
            $item->total_regular_mini,
            $item->total_lentur_besar,
            $item->total_lentur_sedang,
            $item->total_lentur_mini,
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
