<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * VerifikasiDokumenRequest — F14
 *
 * Validasi aksi verifikasi kelengkapan dokumen izin oleh HR.
 *
 * Business rules:
 *   - Aksi hanya boleh 'tandai_lengkap' atau 'tandai_tidak_lengkap'
 *   - catatan_dokumen wajib diisi saat aksi = tandai_tidak_lengkap
 *     agar Admin Outsource tahu dokumen apa yang perlu dilengkapi
 */
class VerifikasiDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalisasi alias yang mungkin dikirim frontend
        $aksiMap = [
            'lengkap'        => 'tandai_lengkap',
            'tidak_lengkap'  => 'tandai_tidak_lengkap',
            'tidak lengkap'  => 'tandai_tidak_lengkap',
        ];

        $aksi = strtolower((string) $this->input('aksi', ''));

        if (isset($aksiMap[$aksi])) {
            $this->merge(['aksi' => $aksiMap[$aksi]]);
        }
    }

    public function rules(): array
    {
        return [
            'aksi'            => ['required', Rule::in(['tandai_lengkap', 'tandai_tidak_lengkap'])],
            'catatan_dokumen' => [
                Rule::requiredIf(fn() => $this->input('aksi') === 'tandai_tidak_lengkap'),
                'nullable',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'aksi.required'            => 'Aksi verifikasi tidak boleh kosong.',
            'aksi.in'                  => 'Aksi harus berupa tandai_lengkap atau tandai_tidak_lengkap.',
            'catatan_dokumen.required' => 'Catatan kekurangan dokumen wajib diisi saat menandai tidak lengkap.',
            'catatan_dokumen.min'      => 'Catatan minimal 5 karakter.',
            'catatan_dokumen.max'      => 'Catatan maksimal 500 karakter.',
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
