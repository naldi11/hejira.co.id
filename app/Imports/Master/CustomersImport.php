<?php

namespace App\Imports\Master;

use App\Models\Customer;
use App\Services\NumberGeneratorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CustomersImport implements ToCollection
{
    private $numberGenerator;

    public function __construct()
    {
        $this->numberGenerator = app(NumberGeneratorService::class);
    }

    // ── ENTRY POINT ──────────────────────────────────────────────────────────
    public function collection(Collection $rows)
    {
        $format = $this->detectFormat($rows);
        \Log::info("CustomersImport: format={$format}, total_rows={$rows->count()}");

        if ($format === 'legacy') {
            $this->processLegacy($rows);
        } else {
            $this->processNewTemplate($rows);
        }
    }

    // ── FORMAT DETECTION ─────────────────────────────────────────────────────
    // Legacy file (Daftar Pelanggan.xls): headers at row 16 (index 15)
    // New template (CustomersTemplateExport): headers at row 5 (index 4)
    private function detectFormat(Collection $rows): string
    {
        $row16 = $rows->get(15);
        if ($row16) {
            $vals = array_map(fn($v) => strtolower(trim((string)$v)), $row16->toArray());
            if (in_array('nama', $vals) && in_array('kode', $vals)) {
                return 'legacy';
            }
        }
        return 'new_template';
    }

    // ── LEGACY FORMAT (Daftar Pelanggan.xls) ────────────────────────────────
    // Header row 16 (index 15): B=Kode, D=Nama, I=Alamat, L=Kota, M=Provinsi, N=Telepon
    // Data from row 17 (index 16), with intermittent empty rows — skip by empty name
    private function processLegacy(Collection $rows): void
    {
        // Build column-index map from the header row
        $headerRow = $rows->get(15)?->toArray() ?? [];
        $colMap    = [];
        foreach ($headerRow as $idx => $val) {
            if ($val !== null && trim((string)$val) !== '') {
                $colMap[strtolower(trim((string)$val))] = $idx;
            }
        }

        $nameIdx     = $colMap['nama']     ?? 3;
        $phoneIdx    = $colMap['telepon']  ?? 13;
        $addressIdx  = $colMap['alamat']   ?? 8;
        $cityIdx     = $colMap['kota']     ?? 11;
        $provinceIdx = $colMap['provinsi'] ?? 12;

        $count = 0;
        foreach ($rows->skip(16) as $row) {
            $data = $row->toArray();
            $name = isset($data[$nameIdx]) ? trim((string)$data[$nameIdx]) : '';
            if (!$name) continue;

            $this->upsertCustomer([
                'name'            => $name,
                'type'            => 'Pelanggan Individual',
                'phone'           => $this->val($data, $phoneIdx),
                'address'         => $this->val($data, $addressIdx),
                'city'            => $this->val($data, $cityIdx),
                'province'        => $this->val($data, $provinceIdx),
                'entity_scope'    => 'all',
                'visible_gudang'  => true,
                'visible_jihans'  => true,
                'visible_hendhys' => true,
            ]);
            $count++;
        }

        \Log::info("CustomersImport legacy: processed {$count} rows");
    }

    // ── NEW TEMPLATE FORMAT (CustomersTemplateExport) ────────────────────────
    // Header at row 5 (index 4): Nama Lengkap*, Tipe*, Nomor Telepon, ...
    // Data from row 6 (index 5)
    private function processNewTemplate(Collection $rows): void
    {
        $headerRow = $rows->get(4);
        if (!$headerRow) return;

        // Normalize header labels → snake_case keys
        $headers = [];
        foreach ($headerRow->toArray() as $idx => $val) {
            if ($val !== null && trim((string)$val) !== '') {
                $key = strtolower(trim((string)$val));
                $key = str_replace(['*', ' ', '/'], ['', '_', '_'], $key);
                $key = preg_replace('/_+/', '_', $key);
                $headers[$idx] = trim($key, '_');
            }
        }

        \Log::info('CustomersImport new_template headers: ' . json_encode($headers));

        $count = 0;
        foreach ($rows->skip(5) as $row) {
            $data = $row->toArray();

            // Map raw values to normalized header keys
            $r = [];
            foreach ($headers as $idx => $key) {
                $r[$key] = isset($data[$idx]) && $data[$idx] !== null
                    ? trim((string)$data[$idx])
                    : null;
            }

            $name = $r['nama_lengkap'] ?? $r['nama'] ?? $r['name'] ?? null;
            if (!$name) continue;

            $type        = $r['tipe'] ?? $r['type'] ?? 'Pelanggan Individual';
            $phone       = $r['nomor_telepon'] ?? $r['telepon'] ?? $r['phone'] ?? null;
            $email       = $r['alamat_email'] ?? $r['email'] ?? null;
            $province    = $r['provinsi'] ?? $r['province'] ?? null;
            $city        = $r['kab_kota'] ?? $r['kota'] ?? $r['city'] ?? null;
            $district    = $r['kecamatan'] ?? $r['district'] ?? null;
            $address     = $r['alamat_lengkap'] ?? $r['alamat'] ?? $r['address'] ?? null;
            $notes       = $r['catatan'] ?? $r['notes'] ?? null;
            $rawScope    = $r['entitas_scope'] ?? $r['entity_scope'] ?? '';
            $entityScope = in_array(strtolower($rawScope), ['gudang', 'jihans', 'hendhys', 'all'])
                ? strtolower($rawScope)
                : 'all';

            $this->upsertCustomer([
                'name'            => $name,
                'type'            => $type,
                'phone'           => $phone ?: null,
                'email'           => $email ?: null,
                'province'        => $province ?: null,
                'city'            => $city ?: null,
                'district'        => $district ?: null,
                'address'         => $address ?: null,
                'notes'           => $notes ?: null,
                'entity_scope'    => $entityScope,
                'visible_gudang'  => in_array($entityScope, ['gudang', 'all']),
                'visible_jihans'  => in_array($entityScope, ['jihans', 'all']),
                'visible_hendhys' => in_array($entityScope, ['hendhys', 'all']),
            ]);
            $count++;
        }

        \Log::info("CustomersImport new_template: processed {$count} rows");
    }

    // ── SHARED UPSERT ────────────────────────────────────────────────────────
    private function upsertCustomer(array $attrs): void
    {
        $name  = $attrs['name'];
        $phone = $attrs['phone'] ?? null;

        // Find existing customer by phone OR name
        $customer = null;
        if ($phone) {
            $customer = Customer::withTrashed()
                ->where(fn($q) => $q->where('phone', $phone)->orWhere('name', $name))
                ->first();
        } else {
            $customer = Customer::withTrashed()->where('name', $name)->first();
        }

        if ($customer) {
            // Only overwrite non-null incoming values to avoid blanking existing data
            $update = array_filter($attrs, fn($v) => $v !== null);
            // Booleans must always be applied (array_filter strips false)
            $update['visible_gudang']  = $attrs['visible_gudang'];
            $update['visible_jihans']  = $attrs['visible_jihans'];
            $update['visible_hendhys'] = $attrs['visible_hendhys'];

            $customer->update($update);
            if ($customer->trashed()) {
                $customer->restore();
            }
        } else {
            $code = $this->numberGenerator->generate('CST', 'master_customers', 'code');
            Customer::create(array_merge($attrs, [
                'code'      => $code,
                'is_active' => true,
            ]));
        }
    }

    // ── HELPER ───────────────────────────────────────────────────────────────
    private function val(array $data, int $idx): ?string
    {
        $v = isset($data[$idx]) ? trim((string)$data[$idx]) : '';
        return $v !== '' ? $v : null;
    }
}
