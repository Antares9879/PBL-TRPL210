<?php

namespace App\Http\Requests\AdminOutsource;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * UpdateKaryawanRequest — F07
 *
 * Validasi update data karyawan oleh Admin Outsource.
 * NIK dan nomor_karyawan di-ignore untuk record yang sedang diedit.
 */
class UpdateKaryawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // id_karyawan dari route parameter
        $idKaryawan = $this->route('karyawan');

        return [
            'nama_lengkap'      => ['required', 'string', 'max:100'],
            'email'             => [
                'required', 'email', 'max:100',
                // Cari id_pengguna dari karyawan yang sedang diedit untuk ignore unique
                Rule::unique('pengguna', 'email')->ignore(
                    \App\Models\Karyawan::find($idKaryawan)?->id_pengguna,
                    'id_pengguna'
                ),
            ],
            'nik'               => [
                'required', 'string', 'max:30',
                Rule::unique('karyawan', 'nik')->ignore($idKaryawan, 'id_karyawan'),
            ],
            'nomor_karyawan'    => [
                'required', 'string', 'max:30',
                Rule::unique('karyawan', 'nomor_karyawan')->ignore($idKaryawan, 'id_karyawan'),
            ],
            'posisi'            => ['required', 'string', 'max:100'],
            'id_departemen'     => ['required', 'integer', 'exists:departemen,id_departemen'],
            'tanggal_bergabung' => ['required', 'date', 'before_or_equal:today'],
            'status'            => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required'      => 'Nama lengkap tidak boleh kosong.',
            'email.required'             => 'Email tidak boleh kosong.',
            'email.unique'               => 'Email sudah digunakan oleh akun lain.',
            'nik.required'               => 'NIK tidak boleh kosong.',
            'nik.unique'                 => 'NIK sudah terdaftar di sistem.',
            'nomor_karyawan.required'    => 'Nomor karyawan tidak boleh kosong.',
            'nomor_karyawan.unique'      => 'Nomor karyawan sudah terdaftar.',
            'posisi.required'            => 'Posisi tidak boleh kosong.',
            'id_departemen.required'     => 'Departemen wajib dipilih.',
            'id_departemen.exists'       => 'Departemen tidak ditemukan.',
            'tanggal_bergabung.required' => 'Tanggal bergabung tidak boleh kosong.',
            'status.required'            => 'Status tidak boleh kosong.',
            'status.in'                  => 'Status tidak valid.',
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