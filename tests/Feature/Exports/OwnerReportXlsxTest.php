<?php

namespace Tests\Feature\Exports;

use App\Exports\Owner\RevenueReportExport;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class OwnerReportXlsxTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('owner', 'web');
        $this->owner = User::factory()->create(['entity' => 'all']);
        $this->owner->assignRole('owner');
    }

    public function test_owner_download_matches_displayed_paid_and_net_revenue_with_numeric_rupiah_and_dates(): void
    {
        $this->seedRevenue();
        $this->travelTo(CarbonImmutable::parse('2026-10-14 17:03:04', 'UTC'));
        $filters = ['unit_bisnis' => 'all', 'periode' => 'harian', 'date_from' => '2026-10-14', 'date_to' => '2026-10-15'];
        $expected = [
            ['label' => '2026-10-15', 'jihans' => 1999.75, 'hendhys' => 2000.10, 'izzi' => 5500, 'total' => 9499.85],
            ['label' => '2026-10-14', 'jihans' => 100.25, 'hendhys' => 300.90, 'izzi' => 700, 'total' => 1101.15],
        ];
        $this->actingAs($this->owner)->get(route('owner.reports', $filters))
            ->assertOk()->assertInertia(function (Assert $page) use ($filters, $expected): void {
                $page->component('Owner/Reports')->where('filters', $filters)->has('reportData', 2);
                foreach ($expected as $index => $row) {
                    $page->where("reportData.$index.label", $row['label']);
                    foreach (['jihans', 'hendhys', 'izzi', 'total'] as $key) {
                        $page->where("reportData.$index.$key", fn ($value) => abs($value - $row[$key]) < 0.00001);
                    }
                }
            });

        $workbook = $this->download($filters, 'Laporan_Omset_harian_20261015_000304.xlsx');
        try {
            $this->assertSame(1, $workbook->getSheetCount());
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('Laporan Omset', $sheet->getTitle());
            $this->assertSame('Semua Unit', $sheet->getCell('B2')->getValue());
            $this->assertSame('Harian', $sheet->getCell('B3')->getValue());
            $this->assertSame('14/10/2026', $sheet->getCell('B4')->getFormattedValue());
            $this->assertSame('15/10/2026', $sheet->getCell('B5')->getFormattedValue());
            $this->assertSame('15/10/2026 00:03:04 WIB', $sheet->getCell('B6')->getFormattedValue());
            foreach (['B4', 'B5', 'B6'] as $cell) {
                $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell($cell)->getDataType());
            }
            $this->assertSame(RevenueReportExport::DATETIME_FORMAT, $sheet->getStyle('B6')->getNumberFormat()->getFormatCode());

            foreach ($expected as $index => $row) {
                $number = RevenueReportExport::DATA_START + $index;
                $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('A'.$number)->getDataType());
                $this->assertSame($row['label'], Date::excelToDateTimeObject($sheet->getCell('A'.$number)->getValue())->format('Y-m-d'));
                $this->assertSame(RevenueReportExport::DATE_FORMAT, $sheet->getStyle('A'.$number)->getNumberFormat()->getFormatCode());
                foreach (['B' => 'jihans', 'C' => 'hendhys', 'D' => 'izzi', 'E' => 'total'] as $column => $key) {
                    $cell = $column.$number;
                    $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell($cell)->getDataType());
                    $this->assertEqualsWithDelta($row[$key], $sheet->getCell($cell)->getValue(), 0.00001);
                    $this->assertSame(RevenueReportExport::CURRENCY_FORMAT, $sheet->getStyle($cell)->getNumberFormat()->getFormatCode());
                    $this->assertStringContainsString('Rp', $sheet->getCell($cell)->getFormattedValue());
                }
            }
            $this->assertSame('TOTAL', $sheet->getCell('A12')->getValue());
            foreach (['B12' => 2100, 'C12' => 2301, 'D12' => 6200, 'E12' => 10601] as $cell => $amount) {
                $this->assertEqualsWithDelta($amount, $sheet->getCell($cell)->getValue(), 0.00001);
                $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell($cell)->getDataType());
            }
            $this->assertSame('B10', $sheet->getFreezePane());
            $this->assertSame('A9:E11', $sheet->getAutoFilter()->getRange());
            $this->assertGreaterThanOrEqual(25, $sheet->getColumnDimension('E')->getWidth());
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public static function reportingPeriods(): array
    {
        return [
            'week' => ['mingguan', '2026-41'], 'month' => ['bulanan', '2026-10'],
            'quarter' => ['3_bulanan', '2026-Q4'], 'half-year' => ['6_bulanan', '2026-H2'],
            'year' => ['tahunan', '2026'], 'overall' => ['keseluruhan', 'Keseluruhan'],
        ];
    }

    #[DataProvider('reportingPeriods')]
    public function test_grouped_periods_remain_text_and_keep_the_same_revenue(string $period, string $label): void
    {
        $this->seedRevenue();
        $workbook = $this->download(['periode' => $period, 'date_from' => '2026-10-14', 'date_to' => '2026-10-15']);
        try {
            $sheet = $workbook->getActiveSheet();
            $this->assertSame($label, $sheet->getCell('A10')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A10')->getDataType());
            $this->assertEqualsWithDelta(10601, $sheet->getCell('E10')->getValue(), 0.00001);
            $this->assertSame('TOTAL', $sheet->getCell('A11')->getValue());
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public static function businessUnits(): array
    {
        return [['jihans', 2100], ['hendhys', 2301], ['izzi', 6200]];
    }

    #[DataProvider('businessUnits')]
    public function test_business_unit_filter_limits_values_and_total(string $unit, int $total): void
    {
        $this->seedRevenue();
        $workbook = $this->download(['unit_bisnis' => $unit, 'periode' => 'keseluruhan', 'date_from' => '2026-10-14', 'date_to' => '2026-10-15']);
        try {
            $sheet = $workbook->getActiveSheet();
            foreach (['B' => 'jihans', 'C' => 'hendhys', 'D' => 'izzi'] as $column => $key) {
                $this->assertEqualsWithDelta($unit === $key ? $total : 0, $sheet->getCell($column.'10')->getValue(), 0.00001);
            }
            $this->assertEqualsWithDelta($total, $sheet->getCell('E10')->getValue(), 0.00001);
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public function test_empty_report_and_optional_date_bounds_still_produce_a_valid_workbook(): void
    {
        foreach ([[], ['date_from' => '2026-10-14'], ['date_to' => '2026-10-15']] as $filters) {
            $workbook = $this->download($filters);
            try {
                $sheet = $workbook->getActiveSheet();
                $this->assertSame('TOTAL', $sheet->getCell('A10')->getValue());
                $this->assertEqualsWithDelta(0, $sheet->getCell('E10')->getValue(), 0.00001);
                $this->assertSame('Tidak ada data untuk filter ini.', $sheet->getCell('A11')->getValue());
                $this->assertSame('A9:E9', $sheet->getAutoFilter()->getRange());
                foreach (['B4' => 'date_from', 'B5' => 'date_to'] as $cell => $key) {
                    if (isset($filters[$key])) {
                        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell($cell)->getDataType());
                        $this->assertSame($filters[$key], Date::excelToDateTimeObject($sheet->getCell($cell)->getValue())->format('Y-m-d'));
                    } else {
                        $this->assertSame('Tanpa batas', $sheet->getCell($cell)->getValue());
                    }
                }
            } finally {
                $workbook->disconnectWorksheets();
            }
        }
    }

    public function test_generated_timestamp_converts_both_sides_of_the_utc_day_boundary_to_wib(): void
    {
        foreach (['2026-10-14 16:59:59' => '14/10/2026 23:59:59 WIB', '2026-10-14 17:00:00' => '15/10/2026 00:00:00 WIB'] as $utc => $expected) {
            $this->travelTo(CarbonImmutable::parse($utc, 'UTC'));
            $workbook = $this->download([]);
            try {
                $this->assertSame($expected, $workbook->getActiveSheet()->getCell('B6')->getFormattedValue());
            } finally {
                $workbook->disconnectWorksheets();
            }
        }
    }

    public static function invalidFilters(): array
    {
        return [
            [['format' => 'exe'], 'format'], [['unit_bisnis' => 'unknown'], 'unit_bisnis'],
            [['periode' => 'unknown'], 'periode'], [['date_from' => '2026-02-30'], 'date_from'],
            [['date_to' => '15/10/2026'], 'date_to'],
            [['date_from' => '2026-10-15', 'date_to' => '2026-10-14'], 'date_to'],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_export_filters_are_rejected(array $filters, string $field): void
    {
        $this->actingAs($this->owner)->getJson(route('owner.reports.export', ['format' => 'xlsx', ...$filters]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public function test_csv_remains_the_default_and_pdf_still_downloads(): void
    {
        $this->seedRevenue();
        $csv = $this->actingAs($this->owner)->get(route('owner.reports.export', ['periode' => 'harian', 'date_from' => '2026-10-15', 'date_to' => '2026-10-15']))
            ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('2026-10-15,1999.75,2000.1,5500,9499.85', $csv->streamedContent());
        $this->get(route('owner.reports.export', ['format' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_export_requires_an_owner_account(): void
    {
        $this->get(route('owner.reports.export', ['format' => 'xlsx']))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('owner.reports.export', ['format' => 'xlsx']))->assertForbidden();
    }

    public function test_text_labels_that_resemble_formulas_are_written_as_literal_strings(): void
    {
        $bytes = Excel::raw(new RevenueReportExport([
            ['label' => '=SUM(1,2)', 'jihans' => 123.45, 'hendhys' => 0, 'izzi' => 0, 'total' => 123.45],
        ], 'all', 'keseluruhan'), \Maatwebsite\Excel\Excel::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'owner-xlsx-');
        file_put_contents($path, $bytes);
        $workbook = IOFactory::createReader('Xlsx')->load($path);
        try {
            $cell = $workbook->getActiveSheet()->getCell('A10');
            $this->assertSame('=SUM(1,2)', $cell->getValue());
            $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        } finally {
            $workbook->disconnectWorksheets();
            unlink($path);
        }
    }

    private function download(array $filters, ?string $filename = null): Spreadsheet
    {
        $response = $this->actingAs($this->owner)->get(route('owner.reports.export', ['format' => 'xlsx', ...$filters]));
        $response->assertOk()->assertDownload($filename)->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', file_get_contents($path, length: 2));
        try {
            return IOFactory::createReader('Xlsx')->load($path);
        } finally {
            unlink($path);
        }
    }

    private function seedRevenue(): void
    {
        foreach ([
            ['jihans', '2026-10-15', '1234.50', 'paid'], ['jihans', '2026-10-15', '765.25', 'paid'],
            ['jihans', '2026-10-14', '100.25', 'paid'], ['jihans', '2026-10-16', '111.00', 'paid'],
            ['jihans', '2026-10-15', '999999.00', 'pending'], ['jihans', '2026-10-15', '888888.00', 'cancelled'],
            ['hendhys', '2026-10-15', '2000.10', 'paid'], ['hendhys', '2026-10-14', '300.90', 'paid'],
            ['hendhys', '2026-10-15', '888888.00', 'cancelled'],
        ] as [$entity, $date, $amount, $status]) {
            DB::table($entity.'_transactions')->insert([
                'transaction_number' => (string) Str::uuid(), 'date' => $date, 'time' => '12:00:00',
                'grand_total' => $amount, 'status' => $status, 'created_by' => $this->owner->id,
                'created_at' => $date.' 12:00:00', 'updated_at' => $date.' 12:00:00',
            ]);
        }

        $branch = Branch::create(['code' => 'XLSX-IZZI', 'name' => 'Cabang Uji', 'type' => 'cabang', 'entity' => 'izzi', 'is_active' => true]);
        $shift = CashierShift::create(['user_id' => $this->owner->id, 'branch_id' => $branch->id, 'entity' => 'izzi', 'status' => 'open', 'opened_at' => '2026-10-14 08:00:00', 'starting_cash' => 0]);
        foreach ([
            ['2026-10-15 00:00:00', 5000, 3000, 'part_cancelled'], ['2026-10-15 23:59:59', 2500, 2500, 'completed'],
            ['2026-10-15 12:00:00', 6000, 0, 'cancelled'], ['2026-10-14 23:59:59', 700, 700, 'completed'],
            ['2026-10-16 00:00:00', 900, 900, 'completed'],
        ] as [$createdAt, $total, $netTotal, $status]) {
            DB::table('izzi_sales')->insert([
                'checkout_key' => (string) Str::uuid(), 'checkout_fingerprint' => Str::random(64), 'number' => (string) Str::uuid(),
                'branch_id' => $branch->id, 'branch_name' => $branch->name, 'shift_id' => $shift->id, 'status' => $status,
                'total' => $total, 'net_total' => $netTotal, 'payment_type' => 'cash', 'amount_paid' => $total,
                'created_by' => $this->owner->id, 'actor_name' => $this->owner->name, 'created_at' => $createdAt,
            ]);
        }
    }
}
