<?php

namespace App\Imports\Master;

use App\Models\Brand;
use App\Models\MasterProductTieredPrice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow, \Maatwebsite\Excel\Concerns\WithStartRow
{
    private $numberGenerator;

    public function __construct()
    {
        $this->numberGenerator = app(NumberGeneratorService::class);
    }

    private function parseNumeric($value, $default = 0)
    {
        if ($value === null || trim($value) === '' || trim($value) === '-') {
            return $default;
        }
        return is_numeric($value) ? (float) $value : $default;
    }

    public function headingRow(): int { return 5; }

    public function startRow(): int { return 6; }

    public function collection(Collection $rows)
    {
        // product_id => [[min_qty, price], ...]
        $tiersToSync = [];

        foreach ($rows as $row) {
            if (!isset($row['nama_produk']) || trim($row['nama_produk']) === '') {
                continue;
            }

            $productName = trim($row['nama_produk']);

            // Cek apakah baris ini hanya baris tier tambahan (kolom produk kosong)
            $isAdditionalTierRow = empty($row['kategori']) && empty($row['satuan']) && empty($row['harga_jual']);

            if (!$isAdditionalTierRow) {
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

                $hpp = $this->parseNumeric($row['hpp'] ?? null, 0);
                $sellingPrice = $this->parseNumeric($row['harga_jual'] ?? null, 0);
                $stockMin = $this->parseNumeric($row['stok_min'] ?? null, 0);
                $ppnRate = $this->parseNumeric($row['rate_ppn'] ?? null, 11.00);

                // Cek produk existing (by barcode lalu by nama, termasuk yang di-soft-delete)
                $product = null;
                if (!empty($row['barcode'])) {
                    $product = Product::withTrashed()->where('barcode', $row['barcode'])->first();
                }
                if (!$product) {
                    $product = Product::withTrashed()->where('name', $productName)->first();
                }

                if ($product) {
                    $product->update([
                        'category_id'  => $category->id,
                        'unit_id'      => $unit->id,
                        'brand_id'     => $brandId,
                        'hpp'          => isset($row['hpp']) ? $hpp : $product->hpp,
                        'selling_price'=> isset($row['harga_jual']) ? $sellingPrice : $product->selling_price,
                        'stock_min'    => isset($row['stok_min']) ? $stockMin : $product->stock_min,
                        'ppn_type'     => $ppnType,
                        'ppn_rate'     => isset($row['rate_ppn']) ? $ppnRate : $product->ppn_rate,
                        'product_type' => $productType,
                        'entity_scope' => $entityScope,
                    ]);

                    if ($product->trashed()) {
                        $product->restore();
                    }
                } else {
                    $code = $this->numberGenerator->generate('PRD', 'master_products', 'code');
                    $product = Product::create([
                        'code'            => $code,
                        'barcode'         => $row['barcode'] ?? null,
                        'name'            => $productName,
                        'category_id'     => $category->id,
                        'unit_id'         => $unit->id,
                        'brand_id'        => $brandId,
                        'hpp'             => $hpp,
                        'selling_price'   => $sellingPrice,
                        'stock_min'       => $stockMin,
                        'ppn_type'        => $ppnType,
                        'ppn_rate'        => $ppnRate,
                        'product_type'    => $productType,
                        'entity_scope'    => $entityScope,
                        'visible_gudang'  => in_array($entityScope, ['gudang', 'all']),
                        'visible_jihans'  => in_array($entityScope, ['jihans', 'all']),
                        'visible_hendhys' => in_array($entityScope, ['hendhys', 'all']),
                        'status'          => 'active',
                        'created_by'      => auth()->id() ?? 1,
                    ]);
                }
            } else {
                // Baris tier tambahan — temukan produk yang sudah di-upsert sebelumnya
                $product = Product::withTrashed()->where('name', $productName)->first();
            }

            // Kumpulkan tier jika ada
            if ($product && !empty($row['tier_min_qty']) && $row['tier_min_qty'] !== '') {
                $tiersToSync[$product->id][] = [
                    'min_qty' => $row['tier_min_qty'],
                    'price'   => $row['tier_harga'] ?? 0,
                ];
            }
        }

        // Sync semua tiered prices setelah seluruh baris diproses
        foreach ($tiersToSync as $productId => $tiers) {
            MasterProductTieredPrice::where('product_id', $productId)->delete();
            foreach ($tiers as $tier) {
                MasterProductTieredPrice::create([
                    'product_id' => $productId,
                    'min_qty'    => $tier['min_qty'],
                    'price'      => $tier['price'],
                ]);
            }
        }
    }
}
