<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:200'],
            'isbn' => [
                'sometimes', 'required', 'string', 'size:13',
                Rule::unique('books', 'isbn')->ignore($this->route('book')),
            ],
            'available' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
