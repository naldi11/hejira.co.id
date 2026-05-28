<?php

namespace App\Exports\Master;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class CustomersTemplateExport implements FromArray, WithTitle, WithEvents
{
    const HEADER_ROW = 5;
    const DATA_START = 6;
    const TOTAL_COLS = 10;
    const EMPTY_ROWS = 200;

    public function title(): string { return 'Data Customer'; }

    public function array(): array { return []; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = 'J';

                // ── JUDUL ─────────────────────────────────────────────────────
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA CUSTOMER');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(36);

                // ── SUB-JUDUL ─────────────────────────────────────────────────
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'Sistem Manajemen Bisnis Terpadu — Gudang Tempua / Jihan\'s Food / Hendhys Brownies');
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E5090']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // ── PETUNJUK ──────────────────────────────────────────────────
                $notes = [
                    3 => 'Kolom Nama Lengkap* dan Tipe* wajib diisi. Customer dengan Nama & No. Telepon cocok akan di-UPDATE alih-alih duplikat.',
                    4 => 'Tipe Customer wajib diisi salah satu dari: Pelanggan Individual, Pelanggan Retail, Pelanggan Agen.',
                ];
                foreach ($notes as $row => $text) {
                    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                    $sheet->setCellValue("A{$row}", '  ' . $text);
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font'      => ['size' => 10, 'color' => ['argb' => 'FF404040']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(20);
                }

                // ── HEADER TABEL ──────────────────────────────────────────────
                $headers = [
                    'A' => 'Nama Lengkap*',  'B' => 'Tipe*',           'C' => 'Nomor Telepon',
                    'D' => 'Alamat Email',   'E' => 'Provinsi',        'F' => 'Kab/Kota',
                    'G' => 'Kecamatan',      'H' => 'Alamat Lengkap',  'I' => 'Catatan',
                    'J' => 'Entitas Scope',
                ];
                $hr = self::HEADER_ROW;
                foreach ($headers as $col => $label) {
                    $sheet->setCellValue("{$col}{$hr}", $label);
                }
                $sheet->getStyle("A{$hr}:{$lastCol}{$hr}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
                ]);
                $sheet->getRowDimension($hr)->setRowHeight(24);

                // ── DATA CONTOH ───────────────────────────────────────────────
                $examples = [
                    ['Budi Santoso', 'Pelanggan Individual', '081234567890', 'budi@gmail.com', 'Jawa Timur', 'Surabaya', 'Gubeng', 'Jl. Dharmahusada No. 12', 'Pelanggan loyal', 'all'],
                    ['Pelanggan Retail A', 'Pelanggan Retail', '08876543210', 'retail.a@gmail.com', 'DKI Jakarta', 'Jakarta Selatan', 'Kebayoran Baru', 'Jl. Senopati No. 45', 'Pembelian rutin mingguan', 'jihans'],
                    ['Toko Sejahtera Agen', 'Pelanggan Agen', '089922334455', 'sejahtera.agen@gmail.com', 'Jawa Barat', 'Bandung', 'Coblong', 'Jl. Dago No. 101', 'Agen wilayah Bandung', 'gudang'],
                ];

                foreach ($examples as $i => $row) {
                    $rowNum = self::DATA_START + $i;
                    foreach (array_keys($headers) as $ci => $col) {
                        $sheet->setCellValue("{$col}{$rowNum}", $row[$ci]);
                    }
                    $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                        'font'      => ['size' => 11],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
                    ]);
                    $sheet->getRowDimension($rowNum)->setRowHeight(20);
                }

                // ── PRE-STYLE BARIS KOSONG ────────────────────────────────────
                $lastExample = self::DATA_START + count($examples) - 1;
                $maxRow      = self::DATA_START + self::EMPTY_ROWS - 1;

                $sheet->getStyle("A" . ($lastExample + 1) . ":{$lastCol}{$maxRow}")->applyFromArray([
                    'font'      => ['size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
                ]);
                for ($r = $lastExample + 1; $r <= $maxRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(20);
                }

                // ── LEBAR KOLOM ───────────────────────────────────────────────
                $widths = [
                    'A' => 24, 'B' => 22, 'C' => 18, 'D' => 22,
                    'E' => 16, 'F' => 18, 'G' => 18, 'H' => 32,
                    'I' => 24, 'J' => 16,
                ];
                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // ── DROPDOWN VALIDATION ───────────────────────────────────────
                $dropdowns = [
                    'B' => '"Pelanggan Individual,Pelanggan Retail,Pelanggan Agen"',
                    'J' => '"all,gudang,jihans,hendhys"',
                ];
                foreach ($dropdowns as $col => $formula) {
                    for ($r = self::DATA_START; $r <= $maxRow; $r++) {
                        $v = $sheet->getCell("{$col}{$r}")->getDataValidation();
                        $v->setType(DataValidation::TYPE_LIST);
                        $v->setErrorStyle(DataValidation::STYLE_INFORMATION);
                        $v->setAllowBlank(true);
                        $v->setShowDropDown(false);
                        $v->setFormula1($formula);
                    }
                }
            },
        ];
    }
}
