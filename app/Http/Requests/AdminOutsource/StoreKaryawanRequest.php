<?php

namespace App\Http\Requests\AdminOutsource;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * StoreKaryawanRequest — F07
 *
 * Validasi pembuatan karyawan baru sekaligus akun pengguna oleh Admin Outsource.
 * id_perusahaan otomatis diambil dari profil Admin yang login — tidak dari input form.
 */
class StoreKaryawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Data pengguna (akun) ──────────────────────────────────────────
            'nama_lengkap'           => ['required', 'string', 'max:100'],
            'email'                  => ['required', 'email', 'max:100', 'unique:pengguna,email'],
            'password'               => ['required', 'string', 'min:8', 'confirmed'],

            // ── Data profil karyawan ──────────────────────────────────────────
            'nik'                    => ['required', 'string', 'max:30', 'unique:karyawan,nik'],
            'nomor_karyawan'         => ['required', 'string', 'max:30', 'unique:karyawan,nomor_karyawan'],
            'posisi'                 => ['required', 'string', 'max:100'],
            'id_departemen'          => ['required', 'integer', 'exists:departemen,id_departemen'],
            'tanggal_bergabung'      => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required'      => 'Nama lengkap tidak boleh kosong.',
            'email.required'             => 'Email tidak boleh kosong.',
            'email.unique'               => 'Email sudah digunakan oleh akun lain.',
            'password.required'          => 'Password tidak boleh kosong.',
            'password.min'               => 'Password minimal 8 karakter.',
            'password.confirmed'         => 'Konfirmasi password tidak cocok.',
            'nik.required'               => 'NIK tidak boleh kosong.',
            'nik.unique'                 => 'NIK sudah terdaftar di sistem.',
            'nomor_karyawan.required'    => 'Nomor karyawan tidak boleh kosong.',
            'nomor_karyawan.unique'      => 'Nomor karyawan sudah terdaftar.',
            'posisi.required'            => 'Posisi/jabatan tidak boleh kosong.',
            'id_departemen.required'     => 'Departemen penugasan wajib dipilih.',
            'id_departemen.exists'       => 'Departemen tidak ditemukan.',
            'tanggal_bergabung.required' => 'Tanggal bergabung tidak boleh kosong.',
            'tanggal_bergabung.before_or_equal' => 'Tanggal bergabung tidak boleh di masa depan.',
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