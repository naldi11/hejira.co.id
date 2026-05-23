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

class ProductsTemplateExport implements FromArray, WithTitle, WithEvents
{
    const HEADER_ROW = 5;
    const DATA_START = 6;
    const TOTAL_COLS = 14;
    const EMPTY_ROWS = 200;

    public function title(): string { return 'Data Produk'; }

    public function array(): array { return []; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = 'N';

                // ── JUDUL ─────────────────────────────────────────────────────
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA PRODUK');
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
                    3 => 'Kolom Nama Produk wajib diisi. Produk yang cocok by Barcode/Nama akan di-UPDATE, bukan duplikat.',
                    4 => 'Harga bertingkat: baris tambahan dengan Nama Produk SAMA, kosongkan Kategori-Entitas Scope, isi Tier Min Qty & Tier Harga.',
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
                    'A' => 'Kategori',     'B' => 'Satuan',       'C' => 'Brand',
                    'D' => 'Barcode',      'E' => 'Nama Produk*', 'F' => 'HPP',
                    'G' => 'Harga Jual',   'H' => 'Stok Min',     'I' => 'Tipe PPN',
                    'J' => 'Rate PPN (%)', 'K' => 'Tipe Produk',  'L' => 'Entitas Scope',
                    'M' => 'Tier Min Qty', 'N' => 'Tier Harga',
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
                    ['Frozen Food', 'Pak', 'Jihans', '8991234', 'Ayam Fillet 1Kg',             40000, 50000, 10, 'none',    11, 'INV', 'all',    1,   145000],
                    ['',           '',    '',        '',        'Ayam Fillet 1Kg',             '',    '',    '',  '',        '', '',    '',       50,  142000],
                    ['',           '',    '',        '',        'Ayam Fillet 1Kg',             '',    '',    '',  '',        '', '',    '',       500, 140500],
                    ['Tortilla',   'Pak', '',        '8994567', 'Tortilla Sedang',             12000, 15000, 20, 'include', 11, 'INV', 'jihans', 1,   15000],
                    ['',           '',    '',        '',        'Tortilla Sedang',             '',    '',    '',  '',        '', '',    '',       10,  14000],
                    ['Bahan Baku', 'Kg',  '',        '',        'Tepung Terigu Segitiga Biru', 10000, 11000, 50, 'none',    11, 'INV', 'all',    '',  ''],
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
                    'A' => 18, 'B' => 12, 'C' => 14, 'D' => 16,
                    'E' => 32, 'F' => 14, 'G' => 14, 'H' => 12,
                    'I' => 13, 'J' => 13, 'K' => 14, 'L' => 16,
                    'M' => 14, 'N' => 14,
                ];
                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // ── DROPDOWN VALIDATION ───────────────────────────────────────
                $dropdowns = [
                    'I' => '"none,include,exclude"',
                    'K' => '"INV,NON"',
                    'L' => '"all,gudang,jihans,hendhys"',
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

                // ── FORMAT ANGKA ──────────────────────────────────────────────
                foreach (['F', 'G', 'H', 'J', 'M', 'N'] as $col) {
                    $sheet->getStyle("{$col}" . self::DATA_START . ":{$col}{$maxRow}")
                        ->getNumberFormat()->setFormatCode('#,##0');
                }
            },
        ];
    }
}
