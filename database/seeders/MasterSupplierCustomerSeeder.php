<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterSupplierCustomerSeeder extends Seeder
{
    public function run(): void
    {
        

        $suppliers = [
            ['code' => 'SPL-001', 'name' => 'PT. Frozen Makmur', 'phone' => '081122334455', 'address' => 'Jl. Industri No. 1'],
            ['code' => 'SPL-002', 'name' => 'CV. Roti Sejahtera', 'phone' => '082233445566', 'address' => 'Jl. Terigu No. 12'],
            ['code' => 'SPL-003', 'name' => 'Toko Kemasan Maju', 'phone' => '083344556677', 'address' => 'Jl. Plastik No. 99'],
        ];

        foreach ($suppliers as $supplier) {
            DB::table('master_suppliers')->updateOrInsert(
                ['code' => $supplier['code']],
                array_merge($supplier, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $customers = [
            ['code' => 'CST-001', 'name' => 'Budi Santoso', 'type' => 'Pelanggan Individual', 'phone' => '081234567890'],
            ['code' => 'CST-002', 'name' => 'Siti Agen Frozen', 'type' => 'Pelanggan Agen', 'phone' => '089876543210'],
            ['code' => 'CST-003', 'name' => 'Toko Retail Jaya', 'type' => 'Pelanggan Retail', 'phone' => '085612341234'],
            ['code' => 'CST-004', 'name' => 'Distributor Makanan', 'type' => 'Pelanggan Agen', 'phone' => '081199887766'],
            ['code' => 'CST-005', 'name' => 'Pelanggan Umum', 'type' => 'Pelanggan Individual', 'phone' => '-'],
        ];

        foreach ($customers as $customer) {
            DB::table('master_customers')->updateOrInsert(
                ['code' => $customer['code']],
                array_merge($customer, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
