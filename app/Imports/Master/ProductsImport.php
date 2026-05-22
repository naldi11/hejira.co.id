<?php

namespace App\Imports\Master;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    private $numberGenerator;

    public function __construct()
    {
        $this->numberGenerator = app(NumberGeneratorService::class);
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Abaikan baris kosong
            if (!isset($row['nama_produk']) || trim($row['nama_produk']) === '') {
                continue;
            }

            // Auto-create / Auto-match Kategori
            $categoryName = $row['kategori'] ?? 'Umum';
            $category = ProductCategory::firstOrCreate(
                ['name' => $categoryName],
                ['entity_scope' => 'all']
            );

            // Auto-create / Auto-match Satuan
            $unitName = $row['satuan'] ?? 'Pcs';
            $unit = Unit::firstOrCreate(
                ['name' => $unitName],
                ['abbreviation' => strtoupper(substr($unitName, 0, 3)), 'entity_scope' => 'all']
            );

            // Auto-create / Auto-match Brand (optional)
            $brandId = null;
            if (!empty($row['brand'])) {
                $brand = Brand::firstOrCreate(
                    ['name' => $row['brand']],
                    ['entity_scope' => 'all']
                );
                $brandId = $brand->id;
            }

            // Validasi Data Teks ke ENUM
            $entityScope = strtolower($row['entitas_scope'] ?? 'all');
            if (!in_array($entityScope, ['gudang', 'jihans', 'hendhys', 'all'])) {
                $entityScope = 'all';
            }

            $ppnType = strtolower($row['tipe_ppn'] ?? 'none');
            if (!in_array($ppnType, ['none', 'include', 'exclude'])) {
                $ppnType = 'none';
            }

            $productType = strtoupper($row['tipe_produk'] ?? 'INV');
            if (!in_array($productType, ['INV', 'NON'])) {
                $productType = 'INV';
            }

            // Cek apakah produk sudah ada (Berdasarkan Barcode atau Nama Produk)
            $product = null;
            if (!empty($row['barcode'])) {
                $product = Product::where('barcode', $row['barcode'])->first();
            }

            if (!$product) {
                $product = Product::where('name', $row['nama_produk'])->first();
            }

            if ($product) {
                // Update produk yang sudah ada
                $product->update([
                    'category_id' => $category->id,
                    'unit_id' => $unit->id,
                    'brand_id' => $brandId,
                    'hpp' => $row['hpp'] ?? $product->hpp,
                    'selling_price' => $row['harga_jual'] ?? $product->selling_price,
                    'stock_min' => $row['stok_min'] ?? $product->stock_min,
                    'ppn_type' => $ppnType,
                    'ppn_rate' => $row['rate_ppn'] ?? $product->ppn_rate,
                    'product_type' => $productType,
                    'entity_scope' => $entityScope,
                ]);
            } else {
                // Buat produk baru
                $code = $this->numberGenerator->generate('PRD', 'master_products', 'code');
                Product::create([
                    'code' => $code,
                    'barcode' => $row['barcode'] ?? null,
                    'name' => $row['nama_produk'],
                    'category_id' => $category->id,
                    'unit_id' => $unit->id,
                    'brand_id' => $brandId,
                    'hpp' => $row['hpp'] ?? 0,
                    'selling_price' => $row['harga_jual'] ?? 0,
                    'stock_min' => $row['stok_min'] ?? 0,
                    'ppn_type' => $ppnType,
                    'ppn_rate' => $row['rate_ppn'] ?? 11.00,
                    'product_type' => $productType,
                    'entity_scope' => $entityScope,
                    'status' => 'active',
                    'created_by' => auth()->id() ?? 1,
                ]);
            }
        }
    }
}
