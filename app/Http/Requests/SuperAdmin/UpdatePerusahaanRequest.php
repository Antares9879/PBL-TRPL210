<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePerusahaanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('perusahaan');

        return [
            'nama_perusahaan' => ['required', 'string', 'max:100'],
            'alamat'          => ['nullable', 'string'],
            'no_telepon'      => ['nullable', 'string', 'max:20'],
            'email'           => [
                'nullable', 'email', 'max:100',
                Rule::unique('perusahaan_outsource', 'email')->ignore($id, 'id_perusahaan'),
            ],
            'status'          => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_perusahaan.required' => 'Nama perusahaan tidak boleh kosong.',
            'email.unique'             => 'Email perusahaan sudah digunakan.',
            'email.email'              => 'Format email tidak valid.',
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
