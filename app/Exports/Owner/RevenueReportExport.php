<?php

namespace App\Exports\Owner;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RevenueReportExport implements FromArray, WithEvents, WithTitle
{
    public const HEADER_ROW = 9;

    public const DATA_START = 10;

    public const CURRENCY_FORMAT = '[$Rp-421] #,##0.00';

    public const DATE_FORMAT = 'dd/mm/yyyy';

    public const DATETIME_FORMAT = 'dd/mm/yyyy hh:mm:ss "WIB"';

    private CarbonImmutable $generatedAt;

    public function __construct(
        private array $data,
        private string $unit,
        private string $period,
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
        ?CarbonInterface $generatedAt = null,
    ) {
        $this->generatedAt = CarbonImmutable::instance($generatedAt ?? now())->setTimezone('Asia/Jakarta');
    }

    public function title(): string
    {
        return 'Laporan Omset';
    }

    public function array(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $sheet->getParent()->getProperties()
                ->setTitle('Laporan Omset Owner')
                ->setSubject('Laporan pendapatan dalam rupiah dan waktu WIB');
            $sheet->getDefaultRowDimension()->setRowHeight(21);
            $sheet->mergeCells('A1:E1');
            $this->text($sheet, 'A1', 'LAPORAN OMSET OWNER');
            $sheet->getStyle('A1:E1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(34);

            $units = ['all' => 'Semua Unit', 'jihans' => 'Jihans Food', 'hendhys' => 'Hendhys Brownies', 'izzi' => 'Izzi Kebab'];
            $periods = ['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan', '3_bulanan' => '3 Bulanan', '6_bulanan' => '6 Bulanan', 'tahunan' => 'Tahunan', 'keseluruhan' => 'Keseluruhan'];
            foreach (['A2' => 'Unit Bisnis', 'A3' => 'Periode', 'A4' => 'Tanggal Awal (WIB)', 'A5' => 'Tanggal Akhir (WIB)', 'A6' => 'Dibuat pada (WIB)'] as $cell => $label) {
                $this->text($sheet, $cell, $label);
            }
            $this->text($sheet, 'B2', $units[$this->unit] ?? $this->unit);
            $this->text($sheet, 'B3', $periods[$this->period] ?? $this->period);
            foreach (['B4' => $this->dateFrom, 'B5' => $this->dateTo] as $cell => $value) {
                if ($value !== null) {
                    $sheet->setCellValueExplicit($cell, $this->excelDate($value), DataType::TYPE_NUMERIC);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(self::DATE_FORMAT);
                } else {
                    $this->text($sheet, $cell, 'Tanpa batas');
                }
            }
            $sheet->setCellValueExplicit('B6', Date::dateTimeToExcel($this->generatedAt), DataType::TYPE_NUMERIC);
            $sheet->getStyle('B6')->getNumberFormat()->setFormatCode(self::DATETIME_FORMAT);
            $sheet->getStyle('A2:A6')->getFont()->setBold(true);
            $sheet->mergeCells('A7:E7');
            $this->text($sheet, 'A7', 'Jihans/Hendhys: transaksi lunas. Izzi: omzet bersih setelah pembatalan. Mata uang: Rupiah (IDR).');
            $sheet->getStyle('A7')->getFont()->setItalic(true)->setSize(10);
            $sheet->getStyle('A7')->getAlignment()->setWrapText(true);
            $sheet->getRowDimension(7)->setRowHeight(30);

            foreach (['A' => 'Periode', 'B' => 'Jihans Food', 'C' => 'Hendhys Brownies', 'D' => 'Izzi Kebab (bersih)', 'E' => 'Total Omset'] as $column => $label) {
                $this->text($sheet, $column.self::HEADER_ROW, $label);
            }
            $sheet->getStyle('A'.self::HEADER_ROW.':E'.self::HEADER_ROW)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(28);

            $totals = ['jihans' => 0.0, 'hendhys' => 0.0, 'izzi' => 0.0, 'total' => 0.0];
            foreach ($this->data as $index => $row) {
                $number = self::DATA_START + $index;
                if ($this->period === 'harian') {
                    $sheet->setCellValueExplicit('A'.$number, $this->excelDate($row['label']), DataType::TYPE_NUMERIC);
                    $sheet->getStyle('A'.$number)->getNumberFormat()->setFormatCode(self::DATE_FORMAT);
                } else {
                    // Explicit strings prevent labels resembling formulas from being executed.
                    $this->text($sheet, 'A'.$number, (string) $row['label']);
                }
                foreach (['B' => 'jihans', 'C' => 'hendhys', 'D' => 'izzi', 'E' => 'total'] as $column => $key) {
                    $value = (float) $row[$key];
                    $sheet->setCellValueExplicit($column.$number, $value, DataType::TYPE_NUMERIC);
                    $totals[$key] += $value;
                }
            }

            $totalRow = self::DATA_START + count($this->data);
            $this->text($sheet, 'A'.$totalRow, 'TOTAL');
            foreach (['B' => 'jihans', 'C' => 'hendhys', 'D' => 'izzi', 'E' => 'total'] as $column => $key) {
                $sheet->setCellValueExplicit($column.$totalRow, round($totals[$key], 2), DataType::TYPE_NUMERIC);
            }
            $sheet->getStyle('B'.self::DATA_START.':E'.$totalRow)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
            $sheet->getStyle('A'.$totalRow.':E'.$totalRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']],
            ]);
            $sheet->getStyle('A'.self::HEADER_ROW.':E'.$totalRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD9E1F2');
            if ($this->data === []) {
                $sheet->mergeCells('A'.($totalRow + 1).':E'.($totalRow + 1));
                $this->text($sheet, 'A'.($totalRow + 1), 'Tidak ada data untuk filter ini.');
            }

            $sheet->freezePane('B'.self::DATA_START);
            $sheet->setAutoFilter('A'.self::HEADER_ROW.':E'.max(self::HEADER_ROW, $totalRow - 1));
            foreach (['A' => 25, 'B' => 30, 'C' => 25, 'D' => 25, 'E' => 27] as $column => $width) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(self::HEADER_ROW, self::HEADER_ROW);
            $sheet->getPageSetup()->setPrintArea('A1:E'.($totalRow + ($this->data === [] ? 1 : 0)));
        }];
    }

    private function text(Worksheet $sheet, string $cell, string $value): void
    {
        $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
    }

    private function excelDate(string $date): float
    {
        return Date::dateTimeToExcel(CarbonImmutable::createFromFormat('!Y-m-d', $date, 'Asia/Jakarta'));
    }
}
