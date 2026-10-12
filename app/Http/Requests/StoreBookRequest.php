<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'isbn' => ['required', 'string', 'size:13', 'unique:books,isbn'],
            'available' => ['sometimes', 'boolean'],
        ];
    }
}
