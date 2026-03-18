<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Pengguna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateAkunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // id_pengguna dari route parameter, untuk ignore unique check pada diri sendiri
        $idPengguna = $this->route('akun');

        return [
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email'        => [
                'required', 'email', 'max:100',
                Rule::unique('pengguna', 'email')->ignore($idPengguna, 'id_pengguna'),
            ],
            'role'         => ['required', Rule::in([
                Pengguna::ROLE_SUPER_ADMIN,
                Pengguna::ROLE_HR,
                Pengguna::ROLE_USER_DEPARTEMEN,
                Pengguna::ROLE_ADMIN_OUTSOURCE,
            ])],
            'status'       => ['required', Rule::in([
                Pengguna::STATUS_AKTIF,
                Pengguna::STATUS_NONAKTIF,
            ])],

            // Profil role-specific (conditional, sama seperti store)
            'id_perusahaan' => [
                Rule::requiredIf(fn() => $this->input('role') === Pengguna::ROLE_ADMIN_OUTSOURCE),
                'nullable', 'integer', 'exists:perusahaan_outsource,id_perusahaan',
            ],
            'id_departemen' => [
                Rule::requiredIf(fn() => $this->input('role') === Pengguna::ROLE_USER_DEPARTEMEN),
                'nullable', 'integer', 'exists:departemen,id_departemen',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required'   => 'Nama lengkap tidak boleh kosong.',
            'email.required'          => 'Email tidak boleh kosong.',
            'email.unique'            => 'Email sudah digunakan oleh akun lain.',
            'role.required'           => 'Role tidak boleh kosong.',
            'role.in'                 => 'Role tidak valid.',
            'status.required'         => 'Status tidak boleh kosong.',
            'status.in'               => 'Status tidak valid.',
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
