<?php

namespace App\Http\Requests\AdminOutsource;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * StorePlanningRequest — F08
 *
 * Validasi input planning kerja bulanan oleh Admin Outsource.
 * Data jadwal per karyawan dikirim sebagai array di field 'jadwal'.
 */
class StorePlanningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periode_bulan'             => ['required', 'integer', 'between:1,12'],
            'periode_tahun'             => ['required', 'integer', 'min:2020', 'max:2100'],

            // Array jadwal per karyawan
            'jadwal'                    => ['required', 'array', 'min:1'],
            'jadwal.*.id_karyawan'      => ['required', 'integer', 'exists:karyawan,id_karyawan'],
            'jadwal.*.id_shift'         => ['required', 'integer', 'exists:shift,id_shift'],
            'jadwal.*.tanggal_kerja'    => ['required', 'date'],
            'jadwal.*.is_hari_libur'    => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'periode_bulan.required'          => 'Bulan periode wajib diisi.',
            'periode_bulan.between'           => 'Bulan harus antara 1–12.',
            'periode_tahun.required'          => 'Tahun periode wajib diisi.',
            'jadwal.required'                 => 'Data jadwal tidak boleh kosong.',
            'jadwal.min'                      => 'Minimal satu jadwal wajib diisi.',
            'jadwal.*.id_karyawan.required'   => 'Karyawan pada jadwal wajib diisi.',
            'jadwal.*.id_karyawan.exists'     => 'Karyawan tidak ditemukan.',
            'jadwal.*.id_shift.required'      => 'Shift pada jadwal wajib diisi.',
            'jadwal.*.id_shift.exists'        => 'Shift tidak ditemukan.',
            'jadwal.*.tanggal_kerja.required' => 'Tanggal kerja wajib diisi.',
            'jadwal.*.tanggal_kerja.date'     => 'Format tanggal kerja tidak valid.',
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