<?php

namespace App\Support;

use Illuminate\Http\Request;

class ListPage
{
    public static function paginate($query, Request $request, string $pageName = 'page')
    {
        $input = $request->validate(['per_page' => ['nullable', 'in:20,50,100,all']]);
        $size = $input['per_page'] ?? '20';
        return $query->paginate($size === 'all' ? max(1, (clone $query)->toBase()->getCountForPagination()) : (int) $size, ['*'], $pageName)->withQueryString();
    }

    public static function metadata($paginator): array
    {
        return ['links' => $paginator->linkCollection()->all(), 'total' => $paginator->total(),
            'from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'current_page' => $paginator->currentPage()];
    }
}
