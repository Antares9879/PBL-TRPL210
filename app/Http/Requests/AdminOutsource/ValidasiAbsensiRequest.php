<?php

namespace App\Http\Requests\AdminOutsource;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * ValidasiAbsensiRequest — F10
 *
 * Validasi aksi approve/reject kehadiran karyawan oleh Admin Outsource.
 * Field catatan_penolakan wajib diisi saat aksi = reject.
 */
class ValidasiAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aksi'               => ['required', Rule::in(['approve', 'reject'])],
            'catatan_penolakan'  => [
                Rule::requiredIf(fn() => $this->input('aksi') === 'reject'),
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'aksi.required'              => 'Aksi validasi tidak boleh kosong.',
            'aksi.in'                    => 'Aksi harus approve atau reject.',
            'catatan_penolakan.required' => 'Alasan penolakan wajib diisi saat menolak.',
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