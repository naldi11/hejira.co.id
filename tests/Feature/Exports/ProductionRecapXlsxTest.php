<?php

namespace Tests\Feature\Exports;

use App\Exports\Jihans\ProductionRecapExport;
use App\Models\Branch;
use App\Models\JihansProductionSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class ProductionRecapXlsxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public static function periods(): array
    {
        return [
            'custom inclusive boundaries' => [['date_from' => '2026-08-03', 'date_to' => '2026-08-05'], '2026-08-03', '2026-08-05'],
            'day' => [['periode' => 'hari'], '2026-08-03', '2026-08-03'],
            'Monday to Sunday' => [['periode' => 'minggu'], '2026-08-03', '2026-08-09'],
            'calendar month' => [['periode' => 'bulan'], '2026-08-01', '2026-08-31'],
        ];
    }

    #[DataProvider('periods')]
    public function test_xlsx_matches_screen_and_csv_with_inclusive_dates_and_actual_totals(array $filters, string $start, string $end): void
    {
        $user = $this->admin();
        $product = $this->product('Tortilla, "Spesial" \\ Kemasan');
        $duplicateName = $this->product($product->name);
        $formulaName = $this->product('=1+1');
        $this->production($user, $product, $start, 1200);
        $this->production($user, $product, $end, 34);
        $this->production($user, $duplicateName, $start, 7);
        $this->production($user, $formulaName, $end, 5);
        $this->production($user, $product, $start, 999, 'prediksi');
        $this->production($user, $product, Carbon::parse($start)->subDay()->toDateString(), 888);
        $this->production($user, $product, Carbon::parse($end)->addDay()->toDateString(), 777);

        $this->actingAs($user);
        $screen = $this->get(route('jihans.production.recap', $filters))->assertOk();
        $screenRows = array_map(fn (array $row) => [$row['product_name'], (int) $row['total_qty']], $screen->inertiaProps('productTotals'));
        $this->assertSame([['=1+1', 5], [$product->name, 1234], [$duplicateName->name, 7]], $screenRows);
        $this->assertFalse($screen->inertiaProps('noFilter'));

        $csv = $this->get(route('jihans.production.recap.export', $filters))->assertOk()->streamedContent();
        $csvRows = array_map(fn (string $row) => str_getcsv($row, escape: ''), explode("\n", trim(substr($csv, 3))));
        $this->assertSame(['Nama Produk', 'Total Jumlah Produksi'], array_shift($csvRows));
        $this->assertSame($screenRows, array_map(fn (array $row) => [$row[0], (int) $row[1]], $csvRows));

        $workbook = $this->download($filters);
        try {
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('Rekap Produksi Jihans', $sheet->getTitle());
            $this->assertSame(['Nama Produk', 'Total Jumlah Produksi'], $sheet->rangeToArray('A8:B8', null, false, false)[0]);
            $this->assertSame($screenRows, $sheet->rangeToArray('A9:B11', null, false, false));
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A9')->getDataType());
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('B10')->getDataType());
            $this->assertSame('#,##0', $sheet->getStyle('B10')->getNumberFormat()->getFormatCode());
            $this->assertSame(['JUMLAH JENIS PRODUK', 3], $sheet->rangeToArray('A12:B12', null, false, false)[0]);
            foreach (['B3' => $start, 'B4' => $end] as $cell => $expected) {
                $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell($cell)->getDataType());
                $this->assertSame($expected, Date::excelToDateTimeObject($sheet->getCell($cell)->getValue())->format('Y-m-d'));
                $this->assertSame(ProductionRecapExport::DATE_FORMAT, $sheet->getStyle($cell)->getNumberFormat()->getFormatCode());
            }
            $this->assertSame('2026-08-03 10:00:00', Date::excelToDateTimeObject($sheet->getCell('B5')->getValue())->format('Y-m-d H:i:s'));
            $this->assertSame(ProductionRecapExport::DATETIME_FORMAT, $sheet->getStyle('B5')->getNumberFormat()->getFormatCode());
            $this->assertSame('Aktual', $sheet->getCell('B6')->getValue());
            $this->assertSame('A8:B11', $sheet->getAutoFilter()->getRange());
            $this->assertSame('B9', $sheet->getFreezePane());
            $this->assertTrue($sheet->getStyle('A8')->getFont()->getBold());
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public function test_quick_day_and_generated_timestamp_use_wib_after_utc_midnight_boundary(): void
    {
        // 18:15 UTC is already the following business day in Jakarta.
        Carbon::setTestNow(Carbon::parse('2026-08-02 18:15:30', 'UTC'));
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');

        try {
            $user = $this->admin();
            $product = $this->product('Tortilla');
            $this->production($user, $product, '2026-08-03', 17);
            $this->production($user, $product, '2026-08-02', 99);
            $this->actingAs($user);
            $workbook = $this->download(['periode' => 'hari'], '20260803_011530');
            try {
                $sheet = $workbook->getActiveSheet();
                $this->assertSame(17, $sheet->getCell('B9')->getValue());
                $this->assertSame('2026-08-03', Date::excelToDateTimeObject($sheet->getCell('B3')->getValue())->format('Y-m-d'));
                $this->assertSame('2026-08-03 01:15:30', Date::excelToDateTimeObject($sheet->getCell('B5')->getValue())->format('Y-m-d H:i:s'));
            } finally {
                $workbook->disconnectWorksheets();
            }
        } finally {
            date_default_timezone_set('Asia/Jakarta');
        }
    }

    public function test_empty_unbounded_export_retains_headers_zero_total_and_filter_state(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('jihans.production.recap'))->assertOk()->assertInertia(fn ($page) => $page->where('noFilter', true)->has('productTotals', 0));
        $workbook = $this->download([]);
        try {
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('Semua tanggal', $sheet->getCell('B2')->getValue());
            $this->assertSame('Tidak dibatasi', $sheet->getCell('B3')->getValue());
            $this->assertSame('Tidak dibatasi', $sheet->getCell('B4')->getValue());
            $this->assertSame(['JUMLAH JENIS PRODUK', 0], $sheet->rangeToArray('A9:B9', null, false, false)[0]);
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('B9')->getDataType());
            $this->assertSame('A8:B8', $sheet->getAutoFilter()->getRange());
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public static function openRanges(): array
    {
        return [
            'only starting date' => [['date_from' => '2026-08-03'], 30, 'B3', 'B4'],
            'only ending date' => [['date_to' => '2026-08-03'], 15, 'B4', 'B3'],
        ];
    }

    #[DataProvider('openRanges')]
    public function test_open_date_range_keeps_unspecified_bound_unlimited(array $filters, int $expected, string $boundedCell, string $unboundedCell): void
    {
        $user = $this->admin();
        $product = $this->product('Tortilla');
        $this->production($user, $product, '2026-08-02', 5);
        $this->production($user, $product, '2026-08-03', 10);
        $this->production($user, $product, '2026-08-04', 20);
        $this->actingAs($user);
        $this->get(route('jihans.production.recap', $filters))->assertOk()->assertInertia(fn ($page) => $page->where('noFilter', false)->where('productTotals.0.total_qty', $expected));
        $workbook = $this->download($filters);
        try {
            $sheet = $workbook->getActiveSheet();
            $this->assertSame($expected, $sheet->getCell('B9')->getValue());
            $this->assertSame('2026-08-03', Date::excelToDateTimeObject($sheet->getCell($boundedCell)->getValue())->format('Y-m-d'));
            $this->assertSame('Tidak dibatasi', $sheet->getCell($unboundedCell)->getValue());
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public static function invalidFilters(): array
    {
        return [
            'invalid date' => [['date_from' => '2026-02-30'], 'date_from'],
            'timestamp supplied for date' => [['date_to' => '2026-08-03T10:00:00Z'], 'date_to'],
            'reversed range' => [['date_from' => '2026-08-05', 'date_to' => '2026-08-03'], 'date_to'],
            'unknown period' => [['periode' => 'tahun'], 'periode'],
            'unknown format' => [['format' => 'xls'], 'format'],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_screen_and_export_reject_invalid_filters(array $filters, string $field): void
    {
        $this->actingAs($this->admin());
        foreach (['jihans.production.recap', 'jihans.production.recap.export'] as $route) {
            $this->getJson(route($route, $filters))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    public function test_export_requires_login_and_production_admin_role(): void
    {
        $url = route('jihans.production.recap.export', ['format' => 'xlsx']);
        $this->get($url)->assertRedirect(route('login'));
        Role::findOrCreate('kasir_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('kasir_jihans');
        $this->actingAs($user)->get($url)->assertForbidden();
    }

    private function download(array $filters, string $timestamp = '20260803_100000'): Spreadsheet
    {
        $response = $this->get(route('jihans.production.recap.export', [...$filters, 'format' => 'xlsx']));
        $response->assertOk()->assertDownload('recap_produksi_jihans_'.$timestamp.'.xlsx');
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $probe = new Spreadsheet;
        try {
            $probe->getActiveSheet()->setCellValue('A1', '1234');
            $this->assertSame(DataType::TYPE_NUMERIC, $probe->getActiveSheet()->getCell('A1')->getDataType(), 'The download must preserve normal numeric binding for subsequent workbook reads.');
        } finally {
            $probe->disconnectWorksheets();
        }
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            Cell::setValueBinder(new DefaultValueBinder);

            return IOFactory::createReader('Xlsx')->load($path);
        } finally {
            unlink($path);
        }
    }

    private function admin(): User
    {
        Role::findOrCreate('admin_jihans', 'web');
        $branch = Branch::create(['code' => 'XLSX-JHS', 'name' => 'Cabang Uji', 'entity' => 'jihans', 'type' => 'cabang', 'is_active' => true]);
        $user = User::factory()->create(['entity' => 'jihans', 'branch_id' => $branch->id]);
        $user->assignRole('admin_jihans');

        return $user;
    }

    private function product(string $name): Product
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Tortilla', 'entity_scope' => 'jihans']);
        $unit = Unit::firstOrCreate(['name' => 'Pcs', 'abbreviation' => 'PCS', 'entity_scope' => 'jihans']);

        return Product::create([
            'code' => 'XLSX-'.(Product::withTrashed()->count() + 1), 'name' => $name,
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'entity_scope' => 'jihans', 'product_type' => 'INV', 'source_type' => 'produced', 'status' => 'active',
        ]);
    }

    private function production(User $user, Product $product, string $date, int $quantity, string $type = 'aktual'): void
    {
        $session = JihansProductionSession::create([
            'session_number' => 'XLSX-'.(JihansProductionSession::count() + 1),
            'date' => $date, 'type' => $type, 'created_by' => $user->id,
        ]);
        $session->details()->create(['product_id' => $product->id, 'quantity' => $quantity]);
    }
}
