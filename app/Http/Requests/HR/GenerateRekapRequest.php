<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * GenerateRekapRequest — F15
 *
 * Validasi input generate rekap absensi bulanan oleh HR.
 *
 * Business rules:
 *   - Bulan dan tahun wajib diisi dan dalam rentang valid.
 *   - id_departemen dan id_perusahaan bersifat opsional sebagai filter.
 *   - Jika keduanya tidak diisi, rekap digenerate untuk semua karyawan aktif.
 */
class GenerateRekapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan'         => ['required', 'integer', 'between:1,12'],
            'tahun'         => ['required', 'integer', 'min:2020', 'max:2100'],
            'id_departemen' => ['nullable', 'integer', 'exists:departemen,id_departemen'],
            'id_perusahaan' => ['nullable', 'integer', 'exists:perusahaan_outsource,id_perusahaan'],
        ];
    }

    public function messages(): array
    {
        return [
            'bulan.required'        => 'Bulan periode wajib diisi.',
            'bulan.between'         => 'Bulan harus antara 1 dan 12.',
            'tahun.required'        => 'Tahun periode wajib diisi.',
            'tahun.min'             => 'Tahun tidak boleh sebelum 2020.',
            'id_departemen.exists'  => 'Departemen tidak ditemukan.',
            'id_perusahaan.exists'  => 'Perusahaan outsource tidak ditemukan.',
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
