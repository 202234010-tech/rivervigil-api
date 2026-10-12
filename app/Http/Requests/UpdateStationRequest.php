<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:150'],
            'river_basin' => ['sometimes', 'required', 'string', 'max:100'],
            'location' => ['sometimes', 'required', 'string', 'max:200'],
            'current_level_cm' => ['sometimes', 'required', 'numeric', 'min:0'],
            'flow_rate_m3s' => ['sometimes', 'required', 'numeric', 'min:0'],
            'alert_status' => ['sometimes', 'required', 'string', Rule::in(['normal', 'siaga_3', 'siaga_2', 'siaga_1'])],
            'thresholds_cm' => ['sometimes', 'required', 'array'],
            'thresholds_cm.normal' => ['sometimes', 'required', 'numeric', 'min:0'],
            'thresholds_cm.siaga_3' => ['sometimes', 'required', 'numeric', 'min:0'],
            'thresholds_cm.siaga_2' => ['sometimes', 'required', 'numeric', 'min:0'],
            'thresholds_cm.siaga_1' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $formattedErrors = [];
        foreach ($validator->errors()->messages() as $field => $messages) {
            foreach ($messages as $message) {
                $formattedErrors[] = [
                    'field' => $field,
                    'issue' => $message,
                ];
            }
        }

        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validasi entitas gagal. Periksa kembali atribut payload yang dikirimkan.',
            'errors' => $formattedErrors,
        ], 422));
    }
}
