<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class BulkVerifikasiDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'hr';
    }

    public function rules(): array
    {
        return [
            'ids' => [
                'required',
                'array',
                'min:1',
            ],
            'ids.*' => [
                'required',
                'integer',
                'distinct',
            ],
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

    public function messages(): array
    {
        return [
            'ids.required' => 'Daftar ID pengajuan wajib diisi.',
            'ids.array' => 'Format ID pengajuan tidak valid.',
            'ids.min' => 'Pilih minimal satu pengajuan untuk aksi bulk.',
            'ids.*.integer' => 'ID pengajuan harus berupa angka.',
            'ids.*.distinct' => 'ID pengajuan tidak boleh duplikat.',
            'aksi.required' => 'Aksi verifikasi wajib dipilih.',
            'aksi.in' => 'Aksi verifikasi tidak valid.',
            'catatan_dokumen.required_if' => 'Catatan kekurangan dokumen wajib diisi saat menandai tidak lengkap.',
            'catatan_dokumen.max' => 'Catatan dokumen maksimal 500 karakter.',
        ];
    }
}
