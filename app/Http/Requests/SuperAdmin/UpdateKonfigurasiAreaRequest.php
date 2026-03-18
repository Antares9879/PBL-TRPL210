<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateKonfigurasiAreaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nama_area'       => ['required', 'string', 'max:100'],
            'latitude_pusat'  => ['required', 'numeric', 'between:-90,90'],
            'longitude_pusat' => ['required', 'numeric', 'between:-180,180'],
            'radius_meter'    => ['required', 'integer', 'min:1'],
            'is_aktif'        => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_area.required'       => 'Nama area tidak boleh kosong.',
            'latitude_pusat.required'  => 'Latitude tidak boleh kosong.',
            'latitude_pusat.between'   => 'Latitude harus antara -90 dan 90.',
            'longitude_pusat.required' => 'Longitude tidak boleh kosong.',
            'longitude_pusat.between'  => 'Longitude harus antara -180 dan 180.',
            'radius_meter.required'    => 'Radius tidak boleh kosong.',
            'radius_meter.min'         => 'Radius minimal 1 meter.',
            'is_aktif.required'        => 'Status aktif tidak boleh kosong.',
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
