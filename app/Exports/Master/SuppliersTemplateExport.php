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

class SuppliersTemplateExport implements FromArray, WithTitle, WithEvents
{
    const HEADER_ROW = 5;
    const DATA_START = 6;
    const TOTAL_COLS = 7;
    const EMPTY_ROWS = 200;

    public function title(): string { return 'Data Supplier'; }

    public function array(): array { return []; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = 'G';

                // ── JUDUL ─────────────────────────────────────────────────────
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA SUPPLIER');
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
                    3 => 'Kolom Nama Supplier* wajib diisi. Supplier dengan Nama / No. Telepon cocok akan di-UPDATE alih-alih duplikat.',
                    4 => 'Entitas Scope wajib diisi salah satu dari: all, gudang, jihans, hendhys (default: all).',
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
                    'A' => 'Nama Supplier*',  'B' => 'Contact Person',  'C' => 'Nomor Telepon',
                    'D' => 'Alamat Email',   'E' => 'Alamat',          'F' => 'Catatan',
                    'G' => 'Entitas Scope',
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
                    ['Pabrik Tortilla Utama', 'Budi Santoso', '081234567890', 'pabrik.tortilla@gmail.com', 'Jl. Raya Industri No. 12', 'Pemasok utama kulit tortilla', 'all'],
                    ['Supplier Daging Sapi Segar', 'Andi Wijaya', '08876543210', 'andi.daging@gmail.com', 'Pasar Induk Block C-12', 'Daging sapi giling per 50kg', 'gudang'],
                    ['Catering Alif Pancake', 'Ibu Alif', '081397280440', 'alifpancake@yahoo.com', 'Jl. Beringin Gang Tiung', 'Supplier pancake durian', 'jihans'],
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
                    'A' => 30, 'B' => 22, 'C' => 18, 'D' => 24,
                    'E' => 35, 'F' => 30, 'G' => 16,
                ];
                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // ── DROPDOWN VALIDATION ───────────────────────────────────────
                $dropdowns = [
                    'G' => '"all,gudang,jihans,hendhys"',
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
