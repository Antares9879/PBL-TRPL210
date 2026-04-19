<?php

namespace App\Http\Requests\AdminOutsource;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * ValidasiIzinRequest — F10 (izin)
 *
 * Validasi aksi approve/reject pengajuan izin oleh Admin Outsource.
 *
 * prepareForValidation() menerima format Indonesia maupun Inggris:
 *   disetujui / approve → approve
 *   ditolak   / reject  → reject
 */
class ValidasiIzinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi nilai 'aksi' sebelum validasi.
     * Frontend mengirim 'disetujui'/'ditolak', backend expects 'approve'/'reject'.
     */
    protected function prepareForValidation(): void
    {
        $aksiMap = [
            'disetujui' => 'approve',
            'setujui'   => 'approve',
            'approve'   => 'approve',
            'ditolak'   => 'reject',
            'tolak'     => 'reject',
            'reject'    => 'reject',
        ];

        $aksi = strtolower((string) $this->input('aksi', ''));

        $this->merge([
            'aksi' => $aksiMap[$aksi] ?? $aksi,
            // Support alias field 'catatan' → 'catatan_penolakan'
            'catatan_penolakan' => $this->input('catatan_penolakan')
                ?? $this->input('catatan'),
        ]);
    }

    public function rules(): array
    {
        return [
            'aksi'              => ['required', Rule::in(['approve', 'reject'])],
            'catatan_penolakan' => [
                Rule::requiredIf(fn() => $this->input('aksi') === 'reject'),
                'nullable', 'string', 'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'aksi.required'              => 'Aksi tidak boleh kosong.',
            'aksi.in'                    => 'Aksi harus approve atau reject.',
            'catatan_penolakan.required' => 'Alasan penolakan wajib diisi.',
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