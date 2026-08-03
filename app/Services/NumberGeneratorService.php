<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NumberGeneratorService
{
    /**
     * Kutip nama kolom sebelum disisipkan ke SQL mentah (orderByRaw).
     *
     * Saat ini semua pemanggil mengirim nama kolom literal, jadi belum ada celah
     * nyata — tapi menginterpolasi identifier mentah ke SQL adalah pola yang mudah
     * berubah jadi injeksi begitu ada pemanggil yang meneruskan input pengguna.
     */
    private function quoteIdentifier(string $column): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            throw new \InvalidArgumentException("Nama kolom tidak valid: {$column}");
        }

        return '`' . $column . '`';
    }

    public function generate(string $prefix, string $table, string $column): string
    {
        $prefixDash = $prefix . '-';
        $like = $prefixDash . '%';

        $last = DB::table($table)
            ->where($column, 'like', $like)
            ->orderByRaw('LENGTH(' . $this->quoteIdentifier($column) . ') DESC')
            ->orderBy($column, 'desc')
            ->lockForUpdate()
            ->value($column);

        if ($last) {
            $numberPart = substr($last, strlen($prefixDash));
            $next = (int) $numberPart + 1;
        } else {
            $next = 1;
        }

        do {
            $padLength = max(4, strlen((string)$next));
            $candidate = $prefixDash . str_pad($next, $padLength, '0', STR_PAD_LEFT);
            $exists = DB::table($table)->where($column, $candidate)->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        return $candidate;
    }

    // Yearly format: PREFIX-YYYY-NNNN (e.g. GDG-PO-20260001)
    public function generateYearly(string $prefix, string $table, string $column): string
    {
        $year = now()->format('Y');
        $prefixYear = $prefix . '-' . $year;
        $like = $prefixYear . '%';

        $last = DB::table($table)
            ->where($column, 'like', $like)
            ->orderByRaw('LENGTH(' . $this->quoteIdentifier($column) . ') DESC')
            ->orderBy($column, 'desc')
            ->lockForUpdate()
            ->value($column);

        if ($last) {
            $numberPart = substr($last, strlen($prefixYear));
            $next = (int) $numberPart + 1;
        } else {
            $next = 1;
        }

        do {
            $padLength = max(4, strlen((string)$next));
            $candidate = $prefixYear . str_pad($next, $padLength, '0', STR_PAD_LEFT);
            $exists = DB::table($table)->where($column, $candidate)->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        return $candidate;
    }
}
