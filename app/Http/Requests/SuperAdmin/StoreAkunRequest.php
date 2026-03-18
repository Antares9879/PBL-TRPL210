<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Pengguna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * StoreAkunRequest
 *
 * Validasi pembuatan akun baru oleh Super Admin.
 * Mencakup data pengguna dasar + data profil role-specific:
 *   - admin_outsource  → wajib ada id_perusahaan
 *   - user_departemen  → wajib ada id_departemen
 *   - hr, super_admin  → tidak ada data profil tambahan
 */
class StoreAkunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Data pengguna dasar ───────────────────────────────────────
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:100', 'unique:pengguna,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'role'         => ['required', Rule::in([
                Pengguna::ROLE_SUPER_ADMIN,
                Pengguna::ROLE_HR,
                Pengguna::ROLE_USER_DEPARTEMEN,
                Pengguna::ROLE_ADMIN_OUTSOURCE,
                // ROLE_KARYAWAN sengaja tidak dimasukkan — dikelola Admin Outsource
            ])],

            // ── Data profil role-specific (conditional) ───────────────────
            'id_perusahaan' => [
                Rule::requiredIf(fn() => $this->input('role') === Pengguna::ROLE_ADMIN_OUTSOURCE),
                'nullable',
                'integer',
                'exists:perusahaan_outsource,id_perusahaan',
            ],
            'id_departemen' => [
                Rule::requiredIf(fn() => $this->input('role') === Pengguna::ROLE_USER_DEPARTEMEN),
                'nullable',
                'integer',
                'exists:departemen,id_departemen',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required'   => 'Nama lengkap tidak boleh kosong.',
            'email.required'          => 'Email tidak boleh kosong.',
            'email.unique'            => 'Email sudah digunakan oleh akun lain.',
            'password.required'       => 'Password tidak boleh kosong.',
            'password.min'            => 'Password minimal 8 karakter.',
            'password.confirmed'      => 'Konfirmasi password tidak cocok.',
            'role.required'           => 'Role tidak boleh kosong.',
            'role.in'                 => 'Role tidak valid.',
            'id_perusahaan.required'  => 'Admin Outsource wajib memiliki perusahaan.',
            'id_perusahaan.exists'    => 'Perusahaan tidak ditemukan.',
            'id_departemen.required'  => 'User Departemen wajib memiliki departemen.',
            'id_departemen.exists'    => 'Departemen tidak ditemukan.',
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
