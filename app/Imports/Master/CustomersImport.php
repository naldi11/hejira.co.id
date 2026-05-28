<?php

namespace App\Imports\Master;

use App\Models\Customer;
use App\Services\NumberGeneratorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToCollection, WithHeadingRow, \Maatwebsite\Excel\Concerns\WithStartRow
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
        foreach ($rows as $row) {
            if (!isset($row['nama_lengkap']) || trim($row['nama_lengkap']) === '') {
                continue;
            }

            $name = trim($row['nama_lengkap']);
            $phone = isset($row['nomor_telepon']) ? trim($row['nomor_telepon']) : null;
            $email = isset($row['alamat_email']) ? trim($row['alamat_email']) : null;

            $type = trim($row['tipe'] ?? 'Pelanggan Individual');
            $validTypes = ['Pelanggan Individual', 'Pelanggan Retail', 'Pelanggan Agen'];
            if (!in_array($type, $validTypes)) {
                $type = 'Pelanggan Individual';
            }

            $province = isset($row['provinsi']) ? trim($row['provinsi']) : null;
            $city = isset($row['kab_kota']) ? trim($row['kab_kota']) : null;
            $district = isset($row['kecamatan']) ? trim($row['kecamatan']) : null;
            $address = isset($row['alamat_lengkap']) ? trim($row['alamat_lengkap']) : null;
            $notes = isset($row['catatan']) ? trim($row['catatan']) : null;

            $entityScope = strtolower(trim($row['entitas_scope'] ?? 'all'));
            if (!in_array($entityScope, ['gudang', 'jihans', 'hendhys', 'all'])) {
                $entityScope = 'all';
            }

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
