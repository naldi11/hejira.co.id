<?php

namespace App\Imports\Master;

use App\Models\Supplier;
use App\Services\NumberGeneratorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SuppliersImport implements ToCollection
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
        \Log::info("SuppliersImport: format={$format}, total_rows={$rows->count()}");

        if ($format === 'legacy') {
            $this->processLegacy($rows);
        } else {
            $this->processNewTemplate($rows);
        }
    }

    // ── FORMAT DETECTION ─────────────────────────────────────────────────────
    // Legacy file (JF Daftar Supplier.xls): headers at row 16 (index 15)
    // New template (SuppliersTemplateExport): headers at row 5 (index 4)
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

    // ── LEGACY FORMAT (JF Daftar Supplier.xls) ────────────────────────────────
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

        $codeIdx     = $colMap['kode']     ?? 1;
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

            // Filter out row if it contains header texts
            if (strtolower($name) === 'nama' || strtolower($name) === 'daftar supplier') continue;

            $code = $this->val($data, $codeIdx);
            $phone = $this->val($data, $phoneIdx);
            $addr = $this->val($data, $addressIdx);
            $city = $this->val($data, $cityIdx);
            $prov = $this->val($data, $provinceIdx);
            $fullAddress = collect([$addr, $city, $prov])->filter()->implode(', ');

            $this->upsertSupplier([
                'code'         => $code ?: null,
                'name'         => $name,
                'phone'        => $phone ?: null,
                'address'      => $fullAddress ?: null,
                'entity_scope' => 'all',
            ]);
            $count++;
        }

        \Log::info("SuppliersImport legacy: processed {$count} rows");
    }

    // ── NEW TEMPLATE FORMAT (SuppliersTemplateExport) ────────────────────────
    // Header at row 5 (index 4): Nama Supplier*, Contact Person, Nomor Telepon, ...
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

        \Log::info('SuppliersImport new_template headers: ' . json_encode($headers));

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

            $name = $r['nama_supplier'] ?? $r['nama'] ?? $r['name'] ?? null;
            if (!$name) continue;

            $contactPerson = $r['contact_person'] ?? null;
            $phone         = $r['nomor_telepon'] ?? $r['telepon'] ?? $r['phone'] ?? null;
            $email         = $r['alamat_email'] ?? $r['email'] ?? null;
            $address       = $r['alamat'] ?? $r['address'] ?? null;
            $notes         = $r['catatan'] ?? $r['notes'] ?? null;
            $rawScope      = $r['entitas_scope'] ?? $r['entity_scope'] ?? '';
            $entityScope   = in_array(strtolower($rawScope), ['gudang', 'jihans', 'hendhys', 'all'])
                ? strtolower($rawScope)
                : 'all';

            $this->upsertSupplier([
                'name'           => $name,
                'contact_person' => $contactPerson ?: null,
                'phone'          => $phone ?: null,
                'email'          => $email ?: null,
                'address'        => $address ?: null,
                'notes'          => $notes ?: null,
                'entity_scope'   => $entityScope,
            ]);
            $count++;
        }

        \Log::info("SuppliersImport new_template: processed {$count} rows");
    }

    // ── SHARED UPSERT ────────────────────────────────────────────────────────
    private function upsertSupplier(array $attrs): void
    {
        $name  = isset($attrs['name']) ? substr(trim((string)$attrs['name']), 0, 150) : '';
        $phone = isset($attrs['phone']) ? substr(trim((string)$attrs['phone']), 0, 20) : null;
        $code  = isset($attrs['code']) ? trim((string)$attrs['code']) : null;

        if ($code && strlen($code) > 20) {
            $code = null;
        }

        // Apply sanitized values to attrs
        $attrs['name']  = $name;
        $attrs['phone'] = $phone;
        $attrs['code']  = $code;

        if (isset($attrs['contact_person'])) {
            $attrs['contact_person'] = substr(trim((string)$attrs['contact_person']), 0, 100);
        }
        if (isset($attrs['email'])) {
            $attrs['email'] = substr(trim((string)$attrs['email']), 0, 100);
        }

        // Find existing supplier by code OR phone OR name
        $supplier = null;
        if ($code) {
            $supplier = Supplier::withTrashed()->where('code', $code)->first();
        }
        if (!$supplier && $phone) {
            $supplier = Supplier::withTrashed()
                ->where(fn($q) => $q->where('phone', $phone)->orWhere('name', $name))
                ->first();
        }
        if (!$supplier) {
            $supplier = Supplier::withTrashed()->where('name', $name)->first();
        }

        if ($supplier) {
            // Only overwrite non-null incoming values to avoid blanking existing data
            $update = array_filter($attrs, fn($v) => $v !== null);
            $supplier->update($update);
            if ($supplier->trashed()) {
                $supplier->restore();
            }
        } else {
            if (!$code) {
                $code = $this->numberGenerator->generate('SUP', 'master_suppliers', 'code');
            } else {
                // double check uniqueness of code
                if (Supplier::withTrashed()->where('code', $code)->exists()) {
                    $code = $this->numberGenerator->generate('SUP', 'master_suppliers', 'code');
                }
            }
            Supplier::create(array_merge($attrs, [
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
