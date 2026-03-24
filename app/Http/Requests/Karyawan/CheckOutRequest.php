<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * CheckOutRequest — F01
 *
 * Validasi data check-out dari karyawan.
 */
class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required'  => 'Koordinat latitude tidak ditemukan. Pastikan GPS aktif.',
            'longitude.required' => 'Koordinat longitude tidak ditemukan. Pastikan GPS aktif.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'Koordinat GPS tidak valid. Pastikan fitur GPS perangkat Anda aktif.',
            'data'    => $validator->errors(),
        ], 422));
    }
}