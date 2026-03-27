<?php

namespace App\Http\Requests\UserDepartemen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * ValidasiLemburRequest — F12
 *
 * Validasi aksi approve/reject pengajuan lembur karyawan
 * oleh User Departemen.
 *
 * Business rules:
 *   - Aksi hanya boleh 'approve' atau 'reject'
 *   - catatan_penolakan wajib diisi saat aksi = reject (sesuai UC-12 skenario alternatif)
 *   - catatan opsional saat approve (bisa diisi keterangan tambahan)
 */
class ValidasiLemburRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aksi'              => ['required', Rule::in(['approve', 'reject'])],
            'catatan_penolakan' => [
                Rule::requiredIf(fn () => $this->input('aksi') === 'reject'),
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Normalisasi nilai 'aksi' yang mungkin dikirim dalam format Indonesia.
     * Menghindari bug jika frontend mengirim 'setujui'/'tolak' bukan 'approve'/'reject'.
     */
    protected function prepareForValidation(): void
    {
        $aksiMap = [
            'setujui'  => 'approve',
            'tolak'    => 'reject',
            'disetujui'=> 'approve',
            'ditolak'  => 'reject',
        ];

        $aksi = strtolower((string) $this->input('aksi', ''));

        if (isset($aksiMap[$aksi])) {
            $this->merge(['aksi' => $aksiMap[$aksi]]);
        }
    }

    public function messages(): array
    {
        return [
            'aksi.required'              => 'Aksi validasi tidak boleh kosong.',
            'aksi.in'                    => 'Aksi harus berupa approve atau reject.',
            'catatan_penolakan.required' => 'Alasan penolakan wajib diisi saat menolak pengajuan lembur.',
            'catatan_penolakan.max'      => 'Alasan penolakan maksimal 500 karakter.',
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