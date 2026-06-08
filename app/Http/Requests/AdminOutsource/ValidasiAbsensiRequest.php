<?php

namespace App\Http\Requests\AdminOutsource;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * ValidasiAbsensiRequest — F10
 *
 * Validasi aksi approve/reject kehadiran karyawan oleh Admin Outsource.
 * Field catatan_penolakan wajib diisi saat aksi = reject.
 */
class ValidasiAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aksi'                  => ['required', Rule::in(['approve', 'reject'])],
            'alasan_penolakan'      => [
                Rule::requiredIf(fn() => $this->input('aksi') === 'reject'),
                'nullable',
                'string',
                'max:200',
            ],
            'keterangan_tambahan'   => [
                'nullable',
                'string',
                'max:500',
                // Wajib jika alasan = "lainnya"
                Rule::requiredIf(fn() => 
                    $this->input('aksi') === 'reject' && 
                    $this->input('alasan_penolakan') === 'lainnya'
                ),
            ],
            // Backward compatibility
            'catatan_penolakan'     => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $aksi = $this->input('aksi');

        if (! $aksi && str_ends_with((string) $this->route()?->getName(), '.reject')) {
            $aksi = 'reject';
        }

        $aksiMap = [
            'disetujui' => 'approve',
            'setujui'   => 'approve',
            'approve'   => 'approve',
            'ditolak'   => 'reject',
            'tolak'     => 'reject',
            'reject'    => 'reject',
        ];

        $aksi = $aksiMap[strtolower((string) $aksi)] ?? $aksi;

        if (! $aksi && ($this->filled('alasan_penolakan') || $this->filled('keterangan_tambahan'))) {
            $aksi = 'reject';
        }

        // Gabungkan alasan_penolakan + keterangan_tambahan jadi catatan lengkap
        $catatanFinal = $this->input('catatan_penolakan', $this->input('catatan'));
        
        if ($this->input('aksi') === 'reject' || $aksi === 'reject') {
            $alasan = $this->input('alasan_penolakan', '');
            $keterangan = $this->input('keterangan_tambahan', '');
            
            if ($alasan && $keterangan) {
                $catatanFinal = $alasan . ' — ' . $keterangan;
            } elseif ($alasan) {
                $catatanFinal = $alasan;
            } elseif ($keterangan) {
                $catatanFinal = $keterangan;
            }
        }

        $this->merge([
            'aksi' => $aksi,
            'catatan_penolakan' => $catatanFinal,
        ]);
    }

    public function messages(): array
    {
        return [
            'aksi.required'                     => 'Aksi validasi tidak boleh kosong.',
            'aksi.in'                           => 'Aksi harus approve atau reject.',
            'alasan_penolakan.required'         => 'Alasan penolakan wajib diisi saat menolak.',
            'alasan_penolakan.max'              => 'Alasan penolakan maksimal 200 karakter.',
            'keterangan_tambahan.required'      => 'Keterangan wajib diisi jika memilih "Lainnya".',
            'keterangan_tambahan.max'           => 'Keterangan tambahan maksimal 500 karakter.',
            'catatan_penolakan.required'        => 'Alasan penolakan wajib diisi saat menolak.',
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
