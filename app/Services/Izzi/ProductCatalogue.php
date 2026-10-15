<?php

namespace App\Services\Izzi;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Izzi reuses the shared product identity and unit; packaging is optional metadata. */
class ProductCatalogue
{
    public function query(?string $search = null): Builder
    {
        return DB::table('master_products as p')
            ->join('master_units as u', 'u.id', '=', 'p.unit_id')
            ->leftJoin('izzi_ingredients as i', 'i.product_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('p.name', 'like', '%'.$search.'%')->orWhere('p.code', 'like', '%'.$search.'%')))
            ->select('p.id', 'p.name', 'p.code', 'p.unit_id', 'p.status', 'u.abbreviation as unit')
            ->selectRaw("COALESCE(NULLIF(i.purchase_unit, ''), u.abbreviation) as purchase_unit")
            ->selectRaw('COALESCE(i.purchase_factor, 1000) as purchase_factor')
            ->selectRaw('CASE WHEN i.product_id IS NULL THEN 0 ELSE 1 END as configured')
            ->orderBy('p.name')->orderBy('p.id');
    }
}
