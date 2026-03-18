<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * LoginRequest
 *
 * Memvalidasi input dari form login sebelum masuk ke AuthApiController.
 * Melempar JSON response (bukan redirect) saat validasi gagal,
 * sesuai format API yang disepakati tim: { status, message, data }.
 */
class LoginRequest extends FormRequest
{
    /**
     * Semua request ke endpoint login diizinkan (belum ada auth).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:6'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email tidak boleh kosong.',
            'email.email'       => 'Format email tidak valid.',
            'email.max'         => 'Email tidak boleh lebih dari 100 karakter.',
            'password.required' => 'Password tidak boleh kosong.',
            'password.min'      => 'Password minimal 6 karakter.',
        ];
    }

    /**
     * Override failedValidation agar mengembalikan JSON, bukan redirect.
     * Ini wajib karena request datang dari AJAX (frontend login.js).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status'  => false,
                'message' => 'Data yang dikirim tidak valid.',
                'data'    => $validator->errors(),
            ], 422)
        );
    }
}
