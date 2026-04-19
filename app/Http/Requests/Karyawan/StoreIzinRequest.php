<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * StoreIzinRequest — F04
 *
 * Validasi pengajuan izin tidak masuk dari karyawan.
 * Mendukung izin single-day maupun multi-day (range tanggal).
 *
 * Aturan tanggal:
 *   - tanggal_izin          : wajib, format date
 *   - tanggal_selesai_izin  : opsional; jika diisi wajib >= tanggal_izin
 *   - Jika tanggal_selesai_izin tidak diisi, dianggap izin 1 hari
 */
class StoreIzinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_jenis_izin'        => ['required', 'integer', 'exists:jenis_izin,id_jenis_izin'],
            'tanggal_izin'         => ['required', 'date'],
            // tanggal_selesai_izin opsional, tapi jika ada wajib >= tanggal_izin
            'tanggal_selesai_izin' => ['nullable', 'date', 'after_or_equal:tanggal_izin'],
            'keterangan'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_jenis_izin.required'               => 'Jenis izin wajib dipilih.',
            'id_jenis_izin.exists'                 => 'Jenis izin tidak valid.',
            'tanggal_izin.required'                => 'Tanggal mulai izin tidak boleh kosong.',
            'tanggal_izin.date'                    => 'Format tanggal mulai izin tidak valid.',
            'tanggal_selesai_izin.date'            => 'Format tanggal selesai izin tidak valid.',
            'tanggal_selesai_izin.after_or_equal'  => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
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