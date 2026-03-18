<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nama_shift'          => ['required', 'string', 'max:50'],
            'jam_masuk'           => ['required', 'date_format:H:i'],
            'jam_pulang'          => ['required', 'date_format:H:i'],
            'durasi_normal_menit' => ['required', 'integer', 'min:1', 'max:1440'],
            'status'              => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_shift.required'        => 'Nama shift tidak boleh kosong.',
            'jam_masuk.required'         => 'Jam masuk tidak boleh kosong.',
            'jam_masuk.date_format'      => 'Format jam masuk harus HH:MM.',
            'jam_pulang.required'        => 'Jam pulang tidak boleh kosong.',
            'jam_pulang.date_format'     => 'Format jam pulang harus HH:MM.',
            'durasi_normal_menit.required'=> 'Durasi normal tidak boleh kosong.',
            'status.required'            => 'Status tidak boleh kosong.',
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
