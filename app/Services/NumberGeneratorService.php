<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NumberGeneratorService
{
    public function generate(string $prefix, string $table, string $column): string
    {
        $like = $prefix . '-%';

        $last = DB::table($table)
            ->where($column, 'like', $like)
            ->orderBy($column, 'desc')
            ->value($column);

        $next = $last
            ? (int) substr($last, strlen($prefix) + 1) + 1
            : 1;

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // Yearly format: PREFIX-YYYY-NNNN (e.g. GDG-PO-20260001)
    public function generateYearly(string $prefix, string $table, string $column): string
    {
        $year = now()->format('Y');
        $like = $prefix . '-' . $year . '%';

        $last = DB::table($table)
            ->where($column, 'like', $like)
            ->orderBy($column, 'desc')
            ->value($column);

        $next = $last
            ? (int) substr($last, -4) + 1
            : 1;

        return $prefix . '-' . $year . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
