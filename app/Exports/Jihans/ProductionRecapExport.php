<?php

namespace App\Exports\Jihans;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductionRecapExport implements FromArray, WithColumnWidths, WithEvents, WithStrictNullComparison, WithTitle
{
    public const DATE_FORMAT = '[$-421]dd mmmm yyyy';

    public const DATETIME_FORMAT = '[$-421]dd mmmm yyyy hh:mm:ss "WIB"';

    public function __construct(
        protected Collection $productTotals,
        protected ?CarbonInterface $dateFrom,
        protected ?CarbonInterface $dateTo,
        protected CarbonInterface $generatedAt,
        protected ?string $periode = null,
    ) {}

    public function title(): string
    {
        return 'Rekap Produksi Jihans';
    }

    public function array(): array
    {
        $period = match ($this->periode) {
            'hari' => 'Hari ini (WIB)',
            'minggu' => 'Minggu ini, Senin–Minggu (WIB)',
            'bulan' => 'Bulan ini (WIB)',
            default => $this->dateFrom || $this->dateTo ? 'Rentang tanggal (WIB)' : 'Semua tanggal',
        };

        $rows = [
            ['REKAP PRODUKSI JIHANS'],
            ['Periode', $period],
            ['Tanggal mulai', $this->dateFrom ? Date::dateTimeToExcel($this->dateFrom->copy()->setTimezone('Asia/Jakarta')->startOfDay()) : 'Tidak dibatasi'],
            ['Tanggal akhir', $this->dateTo ? Date::dateTimeToExcel($this->dateTo->copy()->setTimezone('Asia/Jakarta')->startOfDay()) : 'Tidak dibatasi'],
            ['Dibuat pada', Date::dateTimeToExcel($this->generatedAt->copy()->setTimezone('Asia/Jakarta'))],
            ['Jenis produksi', 'Aktual'],
            [null, null],
            ['Nama Produk', 'Total Jumlah Produksi'],
        ];

        foreach ($this->productTotals as $product) {
            $rows[] = [(string) $product->product_name, (int) $product->total_qty];
        }

        $rows[] = ['JUMLAH JENIS PRODUK', $this->productTotals->count()];

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 48, 'B' => 42];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $dataEndRow = 8 + $this->productTotals->count();
            $totalRow = $dataEndRow + 1;

            // Preserve names and leading zeroes as text without changing the global binder.
            foreach ($this->productTotals->values() as $index => $product) {
                $sheet->setCellValueExplicit('A'.(9 + $index), (string) $product->product_name, DataType::TYPE_STRING);
            }

            $sheet->mergeCells('A1:B1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A2:A6')->getFont()->setBold(true);
            $sheet->getStyle('B3:B4')->getNumberFormat()->setFormatCode(self::DATE_FORMAT);
            $sheet->getStyle('B5')->getNumberFormat()->setFormatCode(self::DATETIME_FORMAT);
            $sheet->getStyle('A8:B8')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A8:B8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
            $sheet->getStyle('A8:B8')->getAlignment()->setWrapText(true);
            $sheet->getRowDimension(8)->setRowHeight(30);
            $sheet->getStyle('B9:B'.$totalRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A'.$totalRow.':B'.$totalRow)->getFont()->setBold(true);
            $sheet->getStyle('A'.$totalRow.':B'.$totalRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCFBF1');
            $sheet->setAutoFilter('A8:B'.$dataEndRow);
            $sheet->freezePane('B9');
            $sheet->setSelectedCell('A1');
        }];
    }
}
