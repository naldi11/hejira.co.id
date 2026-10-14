<?php

namespace App\Support;

use Illuminate\Http\Request;

class ListPage
{
    public static function paginate($query, Request $request, string $pageName = 'page')
    {
        $input = $request->validate(['per_page' => ['nullable', 'in:20,50,100,all']]);
        $size = $input['per_page'] ?? '20';
        if ($size === 'all') {
            $countQuery = clone $query;
            $base = method_exists($countQuery, 'toBase') ? $countQuery->toBase() : $countQuery;
            $size = max(1, $base->getCountForPagination());
        }
        return $query->paginate((int) $size, ['*'], $pageName)->withQueryString();
    }

    public static function metadata($paginator): array
    {
        return ['links' => $paginator->linkCollection()->all(), 'total' => $paginator->total(),
            'from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'current_page' => $paginator->currentPage()];
    }
}
