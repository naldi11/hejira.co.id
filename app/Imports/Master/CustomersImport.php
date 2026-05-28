<?php

namespace App\Imports\Master;

use App\Models\Customer;
use App\Services\NumberGeneratorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class CustomersImport implements ToCollection, WithHeadingRow, WithStartRow
{
    private $numberGenerator;

    public function __construct()
    {
        $this->numberGenerator = app(NumberGeneratorService::class);
    }

    public function headingRow(): int { return 5; }

    public function startRow(): int { return 6; }

    public function collection(Collection $rows)
    {
        // Log number of rows received for import
        \Log::info('CustomersImport: rows count = ' . $rows->count());
        // Log first few rows for debugging (limit 5)
        $rows->take(5)->each(function($row, $index) {
            \Log::info('Row ' . $index . ': ' . json_encode($row->toArray()));
        });

        foreach ($rows as $row) {
            // Prepare a case‑insensitive map of the row
            $rowData = array_change_key_case($row->toArray(), CASE_LOWER);

            // Helper to fetch a value with several possible keys
            $get = function(array $keys) use ($rowData) {
                foreach ($keys as $k) {
                    if (isset($rowData[$k]) && trim($rowData[$k]) !== '') {
                        return trim($rowData[$k]);
                    }
                }
                return null;
            };

            // Nama wajib
            $name = $get(['nama_lengkap', 'nama lengkap', 'name']);
            if (!$name) {
                continue; // lewati baris tanpa nama
            }

            $phone    = $get(['nomor_telepon', 'nomor telepon', 'phone']);
            $email    = $get(['alamat_email', 'alamat email', 'email']);
            $type     = $get(['tipe', 'type']) ?? 'Pelanggan Individual';
            $province = $get(['provinsi', 'province']);
            $city     = $get(['kab_kota', 'kab/kota', 'city']);
            $district = $get(['kecamatan', 'district']);
            $address  = $get(['alamat_lengkap', 'alamat lengkap', 'address']);
            $notes    = $get(['catatan', 'notes']);
            $entityScope = strtolower($get(['entitas_scope', 'entitas scope', 'entity_scope']) ?? 'all');

            // Determine visibility boolean values based on scope
            $visibleGudang  = in_array($entityScope, ['gudang', 'all']);
            $visibleJihans  = in_array($entityScope, ['jihans', 'all']);
            $visibleHendhys = in_array($entityScope, ['hendhys', 'all']);

            // Search for an existing customer. First, try by phone if provided, otherwise by name.
            $customer = null;
            if ($phone !== null && $phone !== '') {
                $customer = Customer::withTrashed()
                    ->where(function($q) use ($phone, $name) {
                        $q->where('phone', $phone)
                          ->orWhere('name', $name);
                    })
                    ->first();
            } else {
                $customer = Customer::withTrashed()->where('name', $name)->first();
            }

            if ($customer) {
                // Update customer
                $customer->update([
                    'type'            => $type,
                    'phone'           => $phone !== null && $phone !== '' ? $phone : $customer->phone,
                    'email'           => $email !== null && $email !== '' ? $email : $customer->email,
                    'province'        => $province !== null ? $province : $customer->province,
                    'city'            => $city !== null ? $city : $customer->city,
                    'district'        => $district !== null ? $district : $customer->district,
                    'address'         => $address !== null ? $address : $customer->address,
                    'notes'           => $notes !== null ? $notes : $customer->notes,
                    'entity_scope'    => $entityScope,
                    'visible_gudang'  => $visibleGudang,
                    'visible_jihans'  => $visibleJihans,
                    'visible_hendhys' => $visibleHendhys,
                ]);

                if ($customer->trashed()) {
                    $customer->restore();
                }
            } else {
                // Create customer
                $code = $this->numberGenerator->generate('CST', 'master_customers', 'code');
                Customer::create([
                    'code'            => $code,
                    'name'            => $name,
                    'type'            => $type,
                    'phone'           => $phone,
                    'email'           => $email,
                    'province'        => $province,
                    'city'            => $city,
                    'district'        => $district,
                    'address'         => $address,
                    'notes'           => $notes,
                    'is_active'       => true,
                    'entity_scope'    => $entityScope,
                    'visible_gudang'  => $visibleGudang,
                    'visible_jihans'  => $visibleJihans,
                    'visible_hendhys' => $visibleHendhys,
                ]);
            }
        }
    }
}
