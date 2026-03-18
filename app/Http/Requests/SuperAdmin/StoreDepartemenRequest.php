<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreDepartemenRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nama_departemen' => ['required', 'string', 'max:100'],
            'kode_departemen' => ['required', 'string', 'max:20', 'unique:departemen,kode_departemen'],
            'status'          => ['sometimes', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_departemen.required' => 'Nama departemen tidak boleh kosong.',
            'kode_departemen.required' => 'Kode departemen tidak boleh kosong.',
            'kode_departemen.unique'   => 'Kode departemen sudah digunakan.',
            'kode_departemen.max'      => 'Kode departemen maksimal 20 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'Data yang dikirim tidak valid.',
            'data'    => $validator->errors(),
        ], 422));
    }
}
