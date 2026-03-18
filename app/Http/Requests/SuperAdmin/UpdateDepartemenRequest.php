<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateDepartemenRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('departemen');

        return [
            'nama_departemen' => ['required', 'string', 'max:100'],
            'kode_departemen' => [
                'required', 'string', 'max:20',
                Rule::unique('departemen', 'kode_departemen')->ignore($id, 'id_departemen'),
            ],
            'status'          => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_departemen.required' => 'Nama departemen tidak boleh kosong.',
            'kode_departemen.required' => 'Kode departemen tidak boleh kosong.',
            'kode_departemen.unique'   => 'Kode departemen sudah digunakan.',
            'status.required'          => 'Status tidak boleh kosong.',
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
