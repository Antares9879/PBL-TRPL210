<?php

namespace App\Http\Requests\AdminOutsource;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * BulkValidasiAbsensiRequest — F10 Bulk Validation
 *
 * Validasi untuk approve/reject multiple absensi sekaligus.
 * Mendukung 2 mode reject:
 * - same_reason: satu alasan untuk semua
 * - individual_reason: alasan terpisah per absensi
 */
class BulkValidasiAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'aksi' => ['required', Rule::in(['approve', 'reject'])],
        ];

        if ($this->input('aksi') === 'approve') {
            $rules['absensi_ids'] = ['required', 'array', 'min:1'];
            $rules['absensi_ids.*'] = ['required', 'integer', 'exists:absensi,id_absensi'];
        }

        if ($this->input('aksi') === 'reject') {
            $rules['mode'] = ['required', Rule::in(['same_reason', 'individual_reason'])];

            if ($this->input('mode') === 'same_reason') {
                $rules['absensi_ids'] = ['required', 'array', 'min:1'];
                $rules['absensi_ids.*'] = ['required', 'integer', 'exists:absensi,id_absensi'];
                $rules['alasan_penolakan'] = ['required', 'string', 'max:200'];
                $rules['keterangan_tambahan'] = ['nullable', 'string', 'max:500'];
            }

            if ($this->input('mode') === 'individual_reason') {
                $rules['rejections'] = ['required', 'array', 'min:1'];
                $rules['rejections.*.id'] = ['required', 'integer', 'exists:absensi,id_absensi'];
                $rules['rejections.*.alasan_penolakan'] = ['required', 'string', 'max:200'];
                $rules['rejections.*.keterangan_tambahan'] = ['nullable', 'string', 'max:500'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'aksi.required' => 'Aksi validasi tidak boleh kosong.',
            'aksi.in' => 'Aksi harus approve atau reject.',
            'mode.required' => 'Mode penolakan tidak boleh kosong.',
            'mode.in' => 'Mode harus same_reason atau individual_reason.',
            'absensi_ids.required' => 'Pilih minimal satu absensi.',
            'absensi_ids.array' => 'Format data absensi tidak valid.',
            'absensi_ids.min' => 'Pilih minimal satu absensi.',
            'absensi_ids.*.exists' => 'Salah satu absensi tidak ditemukan.',
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
            'alasan_penolakan.max' => 'Alasan penolakan maksimal 200 karakter.',
            'keterangan_tambahan.max' => 'Keterangan tambahan maksimal 500 karakter.',
            'rejections.required' => 'Data penolakan tidak boleh kosong.',
            'rejections.min' => 'Minimal satu penolakan diperlukan.',
            'rejections.*.alasan_penolakan.required' => 'Setiap absensi harus memiliki alasan penolakan.',
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
