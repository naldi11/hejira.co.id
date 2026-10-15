<?php

namespace Tests\Feature\Exports;

use App\Models\Branch;
use App\Models\JihansProductionSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class ExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public static function templateDownloads(): array
    {
        $templates = [
            'products' => ['Produk_Template.xlsx', 'Data Produk', 'E5', 'Nama Produk*', 'E6', 'Ayam Fillet 1Kg', 'I6', '"none,include,exclude"'],
            'customers' => ['Template_Import_Customer.xlsx', 'Data Customer', 'A5', 'Nama Lengkap*', 'C6', '081234567890', 'B6', '"Pelanggan Individual,Pelanggan Retail,Pelanggan Agen"'],
            'suppliers' => ['Template_Import_Supplier.xlsx', 'Data Supplier', 'A5', 'Nama Supplier*', 'C6', '081234567890', 'G6', '"all,gudang,jihans,hendhys"'],
        ];

        $cases = [];
        foreach (['gudang' => ['master.', 'super_admin'], 'jihans' => ['jihans.master.', 'admin_jihans'], 'hendhys' => ['hendhys.master.', 'admin_hendhys']] as $entity => [$prefix, $role]) {
            foreach ($templates as $resource => $expected) {
                $cases[$entity.'.'.$resource] = [$entity, $role, $prefix.$resource.'.template', ...$expected];
            }
        }

        return $cases;
    }

    #[DataProvider('templateDownloads')]
    public function test_authorized_download_is_a_readable_xlsx_with_expected_content(
        string $entity,
        string $role,
        string $route,
        string $filename,
        string $sheetTitle,
        string $headingCell,
        string $heading,
        string $sampleCell,
        string $sample,
        string $dropdownCell,
        string $dropdownFormula,
    ): void {
        $response = $this->actingAs($this->user($entity, $role))->get(route($route));
        $response->assertOk()->assertDownload($filename);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);

        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', file_get_contents($path, length: 2));

        // Read the actual generated workbook, rather than mocking Excel::download().
        $workbook = IOFactory::createReader('Xlsx')->load($path);
        try {
            $this->assertSame(1, $workbook->getSheetCount());
            $sheet = $workbook->getActiveSheet();
            $this->assertSame($sheetTitle, $sheet->getTitle());
            $this->assertSame($heading, $sheet->getCell($headingCell)->getValue());
            $this->assertSame($sample, $sheet->getCell($sampleCell)->getValue());
            $validation = $sheet->getCell($dropdownCell)->getDataValidation();
            $this->assertSame(DataValidation::TYPE_LIST, $validation->getType());
            $this->assertSame($dropdownFormula, $validation->getFormula1());

            if ($entity === 'gudang' && $sheetTitle === 'Data Produk') {
                $this->assertSame(40000, $sheet->getCell('F6')->getValue());
                $this->assertSame('#,##0', $sheet->getStyle('F6')->getNumberFormat()->getFormatCode());
                $this->assertSame(50, $sheet->getCell('M7')->getValue());
                $this->assertSame(142000, $sheet->getCell('N7')->getValue());
            }
        } finally {
            $workbook->disconnectWorksheets();
            unlink($path);
        }
    }

    public function test_template_download_requires_an_authorized_account(): void
    {
        foreach (['products', 'customers', 'suppliers'] as $resource) {
            $this->get(route('master.'.$resource.'.template'))->assertRedirect(route('login'));
        }

        $this->actingAs($this->user('all', 'owner'));
        foreach (['products', 'customers', 'suppliers'] as $resource) {
            $this->get(route('master.'.$resource.'.template'))->assertForbidden();
        }
    }

    public function test_production_recap_csv_preserves_product_names_and_filters_actual_production_by_date(): void
    {
        $user = $this->user('jihans', 'admin_jihans');
        $category = ProductCategory::create(['name' => 'Tortilla', 'entity_scope' => 'jihans']);
        $unit = Unit::create(['name' => 'Pcs', 'abbreviation' => 'PCS', 'entity_scope' => 'jihans']);
        $product = Product::create([
            'code' => 'EXPORT-001', 'name' => 'Tortilla, "Spesial" \\ Kemasan',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'entity_scope' => 'jihans', 'product_type' => 'INV', 'source_type' => 'produced',
            'status' => 'active',
        ]);

        foreach ([['aktual', '2026-08-03', 12], ['aktual', '2026-08-03', 8], ['prediksi', '2026-08-03', 99], ['aktual', '2026-08-02', 50]] as $index => [$type, $date, $quantity]) {
            $session = JihansProductionSession::create([
                'session_number' => 'EXPORT-'.$index, 'type' => $type, 'date' => $date, 'created_by' => $user->id,
            ]);
            $session->details()->create(['product_id' => $product->id, 'quantity' => $quantity]);
        }

        $response = $this->actingAs($user)->get(route('jihans.production.recap.export', ['periode' => 'hari']));
        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $rows = array_map(fn (string $row) => str_getcsv($row, escape: ''), explode("\n", trim(substr($csv, 3))));
        $this->assertSame([
            ['Nama Produk', 'Total Jumlah Produksi'],
            [$product->name, '20'],
        ], $rows);
    }

    private function user(string $entity, string $role): User
    {
        Role::findOrCreate($role, 'web');
        $branch = in_array($entity, ['jihans', 'hendhys'], true) ? Branch::create([
            'code' => 'EXPORT-'.strtoupper($entity), 'name' => 'Cabang Uji Ekspor',
            'entity' => $entity, 'type' => 'cabang', 'is_active' => true,
        ]) : null;
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }
}
