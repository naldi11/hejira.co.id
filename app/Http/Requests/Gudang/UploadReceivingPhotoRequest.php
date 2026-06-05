<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;

class UploadReceivingPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photos'       => ['required_without:photo_urls', 'array'],
            'photos.*'     => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'photo_urls'   => ['required_without:photos', 'array'],
            'photo_urls.*' => ['string'],
            'caption'      => ['nullable', 'string', 'max:200'],
        ];
    }
}
