<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * UploadDokumenRequest — F05
 *
 * Validasi upload dokumen pendukung pengajuan izin.
 *
 * Format diterima : PDF, JPG, JPEG, PNG
 * Ukuran maksimum : 2 MB (2048 KB)
 */
class UploadDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dokumen'   => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'dokumen.required' => 'File dokumen pendukung tidak ditemukan.',
            'dokumen.file'     => 'Upload yang diterima harus berupa file.',
            'dokumen.mimes'    => 'Format file tidak didukung. Gunakan PDF, JPG, atau PNG.',
            'dokumen.max'      => 'Ukuran file maksimal 2 MB.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'File tidak valid.',
            'data'    => $validator->errors(),
        ], 422));
    }
}