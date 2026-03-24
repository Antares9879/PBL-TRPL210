<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * StoreLemburRequest — F03
 *
 * Validasi pengajuan lembur retroaktif dari karyawan.
 * Tanggal lembur tidak boleh di masa depan (lembur sudah terjadi).
 * Batas waktu H+1 divalidasi lebih detail di LemburService.
 */
class StoreLemburRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal_lembur'       => ['required', 'date', 'before_or_equal:today'],
            'jam_mulai_estimasi'   => ['required', 'date_format:H:i'],
            'jam_selesai_estimasi' => ['required', 'date_format:H:i'],
            'alasan_lembur'        => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_lembur.required'         => 'Tanggal lembur tidak boleh kosong.',
            'tanggal_lembur.date'             => 'Format tanggal lembur tidak valid.',
            'tanggal_lembur.before_or_equal'  => 'Tanggal lembur tidak boleh di masa depan.',
            'jam_mulai_estimasi.required'     => 'Jam mulai lembur wajib diisi.',
            'jam_mulai_estimasi.date_format'  => 'Format jam mulai harus HH:MM (contoh: 17:00).',
            'jam_selesai_estimasi.required'   => 'Jam selesai lembur wajib diisi.',
            'jam_selesai_estimasi.date_format'=> 'Format jam selesai harus HH:MM (contoh: 20:00).',
            'alasan_lembur.required'          => 'Alasan lembur tidak boleh kosong.',
            'alasan_lembur.min'               => 'Alasan lembur minimal 10 karakter.',
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