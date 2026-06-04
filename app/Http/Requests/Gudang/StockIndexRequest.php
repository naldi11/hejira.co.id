<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;

class StockIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already gated by ['auth', 'check.entity:gudang', 'role:admin_gudang'].
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search'    => ['nullable', 'string', 'max:100'],
            'low_stock' => ['nullable', 'in:1'],
        ];
    }
}
