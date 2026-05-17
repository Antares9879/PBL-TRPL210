<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

/**
 * VerifikasiDokumenRequest
 *
 * Validasi request untuk verifikasi kelengkapan dokumen izin oleh HR.
 *
 * Aksi yang valid:
 *   - tandai_lengkap       → dokumen sudah lengkap
 *   - tandai_tidak_lengkap → dokumen belum lengkap (wajib isi catatan_dokumen)
 */
class VerifikasiDokumenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya HR yang boleh verifikasi dokumen
        return $this->user()?->role === 'hr';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'aksi' => [
                'required',
                'string',
                'in:tandai_lengkap,tandai_tidak_lengkap',
            ],
            'catatan_dokumen' => [
                'required_if:aksi,tandai_tidak_lengkap',
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aksi.required'                    => 'Aksi verifikasi wajib dipilih.',
            'aksi.in'                          => 'Aksi verifikasi tidak valid.',
            'catatan_dokumen.required_if'      => 'Catatan kekurangan dokumen wajib diisi saat menandai tidak lengkap.',
            'catatan_dokumen.max'              => 'Catatan dokumen maksimal 500 karakter.',
        ];
    }
}
