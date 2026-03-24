<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * StoreIzinRequest — F04
 *
 * Validasi pengajuan izin tidak masuk dari karyawan.
 */
class StoreIzinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_jenis_izin' => ['required', 'integer', 'exists:jenis_izin,id_jenis_izin'],
            'tanggal_izin'  => ['required', 'date'],
            'keterangan'    => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_jenis_izin.required' => 'Jenis izin wajib dipilih.',
            'id_jenis_izin.exists'   => 'Jenis izin tidak valid.',
            'tanggal_izin.required'  => 'Tanggal izin tidak boleh kosong.',
            'tanggal_izin.date'      => 'Format tanggal izin tidak valid.',
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