<?php

namespace App\Http\Controllers\Api\AdminOutsource;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\PlanningKerja;
use App\Models\JadwalKerja;
use App\Models\Shift;
use App\Models\Pengguna;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Services\NotifikasiService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * PlanningKerjaApiController — F08, F09
 *
 * F08 — Input planning via:
 *   (a) Upload Excel + SheetJS parse di frontend
 *   (b) Grid interaktif untuk koreksi sel individual
 *
 * F09 — Upload ulang dengan:
 *   (a) Preview diff (berubah / ditambah / dihapus)
 *   (b) Konfirmasi simpan versi baru
 *
 * Endpoints:
 *   GET  /api/admin/planning                          → index()
 *   GET  /api/admin/planning/{id}                     → show()
 *   GET  /api/admin/planning/download-template        → downloadTemplate()
 *   POST /api/admin/planning/upload-excel             → uploadExcel()   (validasi, belum simpan)
 *   POST /api/admin/planning                          → store()         (simpan setelah konfirmasi)
 *   POST /api/admin/planning/preview-diff             → previewDiff()   (diff sebelum upload ulang)
 *   POST /api/admin/planning/{id}/upload-ulang        → uploadUlang()   (simpan versi baru)
 *   PUT  /api/admin/planning/{id}/update-jadwal       → updateJadwal()  (grid: update 1 sel)
 */
class PlanningKerjaApiController extends Controller
{
    private function getIdPerusahaan(): int
    {
        return $this->authenticatedPengguna()->adminOutsourceProfile->id_perusahaan;
    }

    // ── INDEX ─────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $idPerusahaan = $this->getIdPerusahaan();
        $query = PlanningKerja::where('id_perusahaan', $idPerusahaan)->withCount('jadwal');
        if ($request->filled('bulan')) $query->where('periode_bulan', $request->bulan);
        if ($request->filled('tahun'))  $query->where('periode_tahun',  $request->tahun);

        $data = $query->orderByDesc('periode_tahun')->orderByDesc('periode_bulan')
            ->orderByDesc('versi')->paginate(20);
        $data->getCollection()->transform(fn($p) => $this->formatPlanning($p));

        return response()->json(['status' => true, 'message' => 'OK', 'data' => $data]);
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────

    public function show(int $planning): JsonResponse
    {
        $data = $this->findPlanning($planning);
        if (!$data) return $this->notFound();
        $data->load(['jadwal.karyawan:id_karyawan,nama_lengkap,nomor_karyawan','jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang']);
        return response()->json(['status' => true, 'message' => 'OK', 'data' => $this->formatPlanningDetail($data)]);
    }

    // ── DOWNLOAD TEMPLATE (F08) ───────────────────────────────────────────────

    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $bulan        = (int) $request->bulan;
        $tahun        = (int) $request->tahun;
        $idPerusahaan = $this->getIdPerusahaan();
        $namaBulan    = $this->getNamaBulan($bulan);
        $jumlahHari   = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

        $karyawanList = Karyawan::where('id_perusahaan', $idPerusahaan)
            ->where('status', 'aktif')
            ->with('departemen:id_departemen,nama_departemen')
            ->orderBy('nama_lengkap')
            ->get(['id_karyawan','nama_lengkap','nomor_karyawan','posisi','id_departemen']);

        if ($karyawanList->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Tidak ada karyawan aktif.', 'data' => null], 422);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Planning_{$namaBulan}_{$tahun}");

        // ── Style definitions ─────────────────────────────────────────────────
        $sHeader = [
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A6E1A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BBECBB']]],
        ];
        $sTanggal = [
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '164916']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCF5DC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BBECBB']]],
        ];
        $sKaryawan = [
            'font'      => ['size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ];
        $sInput = [
            'font'      => ['size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ];

        // ── Row 1: Judul ──────────────────────────────────────────────────────
        $lastCol = $this->getColLetter(4 + $jumlahHari);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', "TEMPLATE PLANNING KERJA — {$namaBulan} {$tahun}");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A280A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Row 2: Header ─────────────────────────────────────────────────────
        $sheet->setCellValue('A2', 'Nama Karyawan');
        $sheet->setCellValue('B2', 'ID');
        $sheet->setCellValue('C2', 'Departemen');
        $sheet->setCellValue('D2', 'Posisi');
        $sheet->getStyle('A2:D2')->applyFromArray($sHeader);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Header tanggal
        for ($hari = 1; $hari <= $jumlahHari; $hari++) {
            $col = $this->getColLetter(4 + $hari);
            $tgl = \Carbon\Carbon::create($tahun, $bulan, $hari);
            $namaHari = mb_strtoupper(mb_substr($tgl->locale('id')->isoFormat('dd'), 0, 2));
            $sheet->setCellValue("{$col}2", "{$hari}\n{$namaHari}");
            $sheet->getStyle("{$col}2")->applyFromArray($sTanggal);
            $sheet->getColumnDimension($col)->setWidth(5.2);
            if ($tgl->isWeekend()) {
                $sheet->getStyle("{$col}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
                $sheet->getStyle("{$col}2")->getFont()->getColor()->setRGB('92400E');
            }
        }
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(18);

        // ── Row 3+: Karyawan ──────────────────────────────────────────────────
        foreach ($karyawanList as $idx => $k) {
            $row = $idx + 3;
            $sheet->setCellValue("A{$row}", $k->nama_lengkap);
            $sheet->setCellValue("B{$row}", $k->id_karyawan);
            $sheet->setCellValue("C{$row}", $k->departemen->nama_departemen ?? '—');
            $sheet->setCellValue("D{$row}", $k->posisi ?? '—');
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($sKaryawan);
            $sheet->getStyle("A{$row}:D{$row}")->getFont()->getColor()->setRGB('475569');

            for ($hari = 1; $hari <= $jumlahHari; $hari++) {
                $col = $this->getColLetter(4 + $hari);
                $sheet->setCellValue("{$col}{$row}", '');
                $sheet->getStyle("{$col}{$row}")->applyFromArray($sInput);
                $tgl = \Carbon\Carbon::create($tahun, $bulan, $hari);
                if ($tgl->isWeekend()) {
                    $sheet->getStyle("{$col}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFBEB');
                }
            }
            $sheet->getRowDimension($row)->setRowHeight(18);
        }
        $sheet->freezePane('E3');

        // ── Sheet 2: Petunjuk ─────────────────────────────────────────────────
        $p2 = $spreadsheet->createSheet();
        $p2->setTitle('Petunjuk');
        $rows = [
            ['PETUNJUK PENGISIAN TEMPLATE PLANNING KERJA', ''],
            ['', ''],
            ['KODE SHIFT', 'KETERANGAN'],
            ['P', 'Shift Pagi (07:00–15:00)'],
            ['S', 'Shift Siang (15:00–23:00)'],
            ['M', 'Shift Malam (23:00–07:00)'],
            ['N', 'Shift Normal (08:00–17:00)'],
            ['- atau kosong', 'Hari Libur'],
            ['', ''],
            ['ATURAN PENTING', ''],
            ['1.', 'Jangan ubah kolom A, B, C, D (sudah terkunci)'],
            ['2.', 'Isi hanya kolom tanggal dengan kode shift'],
            ['3.', 'Kode tidak case-sensitive: p = P'],
            ['4.', 'Sel kosong = hari libur'],
            ['5.', 'Simpan sebagai .xlsx sebelum upload'],
        ];
        foreach ($rows as $i => $r) {
            $p2->setCellValue("A" . ($i+1), $r[0]);
            $p2->setCellValue("B" . ($i+1), $r[1]);
        }
        $p2->getColumnDimension('A')->setWidth(30);
        $p2->getColumnDimension('B')->setWidth(45);
        $p2->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12]]);
        $p2->getStyle('A3:B3')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A6E1A']]]);

        $spreadsheet->setActiveSheetIndex(0);

        $namaFile = "Template_Planning_{$namaBulan}_{$tahun}.xlsx";
        $writer   = new Xlsx($spreadsheet);
        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $namaFile,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    // ── UPLOAD EXCEL — VALIDASI SAJA (F08) ───────────────────────────────────

    public function uploadExcel(Request $request): JsonResponse
    {
        $request->validate([
            'periode_bulan'              => 'required|integer|between:1,12',
            'periode_tahun'              => 'required|integer|min:2020|max:2100',
            'sheet_name'                 => 'nullable|string',
            'rows'                       => 'required|array|min:1',
            'rows.*.id_karyawan'         => 'required|integer',
            'rows.*.jadwal'              => 'required|array',
        ]);

        $bulan        = (int) $request->periode_bulan;
        $tahun        = (int) $request->periode_tahun;
        $idPerusahaan = $this->getIdPerusahaan();

        // Sanity check nama sheet
        $warnings = [];
        if ($request->filled('sheet_name')) {
            $namaBulan = $this->getNamaBulan($bulan);
            if (!str_contains($request->sheet_name, $namaBulan) || !str_contains($request->sheet_name, (string)$tahun)) {
                $warnings[] = ['pesan' => "Nama sheet '{$request->sheet_name}' tidak cocok dengan periode {$namaBulan} {$tahun}. Pastikan file yang diupload sesuai."];
            }
        }

        // Karyawan valid
        $karyawanValid = Karyawan::where('id_perusahaan', $idPerusahaan)
            ->where('status', 'aktif')->pluck('nama_lengkap', 'id_karyawan')->toArray();

        // Shift mapping: p→id, s→id, m→id, n→id
        $shiftIdMap = [];
        foreach (Shift::where('status', 'aktif')->get() as $s) {
            $n = strtolower($s->nama_shift);
            if (str_contains($n, 'pagi'))   $shiftIdMap['p'] = $s->id_shift;
            if (str_contains($n, 'siang'))  $shiftIdMap['s'] = $s->id_shift;
            if (str_contains($n, 'malam'))  $shiftIdMap['m'] = $s->id_shift;
            if (str_contains($n, 'normal')) $shiftIdMap['n'] = $s->id_shift;
        }

        $errors = []; $valid = []; $totalJadwal = 0; $totalLibur = 0;
        $seen = []; // duplikat check

        foreach ($request->rows as $rowIdx => $row) {
            $idKaryawan = (int) $row['id_karyawan'];
            if (!isset($karyawanValid[$idKaryawan])) {
                $errors[] = ['baris' => $rowIdx+3, 'pesan' => "Karyawan ID {$idKaryawan} tidak ditemukan atau bukan milik perusahaan Anda.", 'tipe' => 'karyawan_tidak_valid'];
                continue;
            }
            $namaK = $karyawanValid[$idKaryawan];

            foreach ($row['jadwal'] as $tglStr => $kodeShift) {
                if (empty($kodeShift) || $kodeShift === '-') { $totalLibur++; continue; }
                $kode = strtolower(trim($kodeShift));
                if (!isset($shiftIdMap[$kode])) {
                    $errors[] = ['baris' => $rowIdx+3, 'karyawan' => $namaK, 'tanggal' => $tglStr, 'nilai' => $kodeShift, 'pesan' => "Kode shift '{$kodeShift}' tidak dikenal. Gunakan P/S/M/N.", 'tipe' => 'kode_shift_invalid'];
                    continue;
                }
                try {
                    $tgl = \Carbon\Carbon::createFromFormat('Y-m-d', $tglStr);
                    if ($tgl->month != $bulan || $tgl->year != $tahun) {
                        $errors[] = ['baris' => $rowIdx+3, 'karyawan' => $namaK, 'tanggal' => $tglStr, 'pesan' => "Tanggal di luar periode.", 'tipe' => 'tanggal_diluar_periode'];
                        continue;
                    }
                } catch (\Exception $e) {
                    $errors[] = ['baris' => $rowIdx+3, 'karyawan' => $namaK, 'tanggal' => $tglStr, 'pesan' => "Format tanggal tidak valid.", 'tipe' => 'format_tanggal_invalid'];
                    continue;
                }
                $dupKey = "{$idKaryawan}|{$tglStr}";
                if (isset($seen[$dupKey])) {
                    $errors[] = ['baris' => $rowIdx+3, 'karyawan' => $namaK, 'tanggal' => $tglStr, 'pesan' => "Duplikat: {$namaK} pada {$tglStr}.", 'tipe' => 'duplikat'];
                    continue;
                }
                $seen[$dupKey] = true;
                $totalJadwal++;
                $valid[] = ['id_karyawan' => $idKaryawan, 'nama_karyawan' => $namaK, 'tanggal_kerja' => $tglStr, 'id_shift' => $shiftIdMap[$kode], 'kode_shift' => strtoupper($kode), 'is_hari_libur' => false];
            }
        }

        $planningExisting = PlanningKerja::where('id_perusahaan', $idPerusahaan)
            ->where('periode_bulan', $bulan)->where('periode_tahun', $tahun)
            ->where('status', PlanningKerja::STATUS_AKTIF)->first();

        return response()->json([
            'status'  => empty($errors),
            'message' => empty($errors) ? "{$totalJadwal} jadwal siap disimpan." : count($errors).' error ditemukan.',
            'data'    => [
                'valid'             => $valid,
                'errors'            => $errors,
                'warnings'          => $warnings,
                'ringkasan'         => [
                    'total_karyawan' => count(array_unique(array_column($valid, 'id_karyawan'))),
                    'total_jadwal'   => $totalJadwal,
                    'total_libur'    => $totalLibur,
                    'total_error'    => count($errors),
                ],
                'planning_existing' => $planningExisting ? [
                    'id_planning'   => $planningExisting->id_planning,
                    'versi'         => $planningExisting->versi,
                    'periode_label' => $planningExisting->periode_label,
                    'jumlah_jadwal' => $planningExisting->jadwal()->count(),
                ] : null,
                'periode_bulan' => $bulan,
                'periode_tahun' => $tahun,
            ],
        ], empty($errors) ? 200 : 422);
    }

    // ── PREVIEW DIFF (F09) ────────────────────────────────────────────────────

    public function previewDiff(Request $request): JsonResponse
    {
        $request->validate([
            'id_planning_lama'           => 'required|integer|exists:planning_kerja,id_planning',
            'jadwal_baru'                => 'required|array|min:1',
            'jadwal_baru.*.id_karyawan'  => 'required|integer',
            'jadwal_baru.*.tanggal_kerja'=> 'required|date',
            'jadwal_baru.*.id_shift'     => 'required|integer',
        ]);

        $planningLama = $this->findPlanning($request->id_planning_lama);
        if (!$planningLama) return $this->notFound();

        $jadwalLama = JadwalKerja::where('id_planning', $planningLama->id_planning)
            ->with(['karyawan:id_karyawan,nama_lengkap','shift:id_shift,nama_shift'])
            ->get()->keyBy(fn($j) => "{$j->id_karyawan}|{$j->tanggal_kerja->format('Y-m-d')}");

        $jadwalBaru = collect($request->jadwal_baru)
            ->keyBy(fn($j) => "{$j['id_karyawan']}|{$j['tanggal_kerja']}");

        $diff = ['diubah' => [], 'ditambah' => [], 'dihapus' => []];

        foreach ($jadwalBaru as $key => $baru) {
            if ($jadwalLama->has($key)) {
                $lama = $jadwalLama[$key];
                if ($lama->id_shift != $baru['id_shift']) {
                    $diff['diubah'][] = [
                        'id_karyawan'   => $baru['id_karyawan'],
                        'nama_karyawan' => $lama->karyawan->nama_lengkap ?? '—',
                        'tanggal'       => $baru['tanggal_kerja'],
                        'shift_lama'    => $lama->shift->nama_shift ?? '—',
                        'shift_baru'    => Shift::find($baru['id_shift'])?->nama_shift ?? '—',
                    ];
                }
            } else {
                $k = Karyawan::find($baru['id_karyawan']);
                $diff['ditambah'][] = [
                    'id_karyawan'   => $baru['id_karyawan'],
                    'nama_karyawan' => $k->nama_lengkap ?? '—',
                    'tanggal'       => $baru['tanggal_kerja'],
                    'shift_baru'    => Shift::find($baru['id_shift'])?->nama_shift ?? '—',
                ];
            }
        }
        foreach ($jadwalLama as $key => $lama) {
            if (!$jadwalBaru->has($key)) {
                $diff['dihapus'][] = [
                    'id_karyawan'   => $lama->id_karyawan,
                    'nama_karyawan' => $lama->karyawan->nama_lengkap ?? '—',
                    'tanggal'       => $lama->tanggal_kerja->format('Y-m-d'),
                    'shift_lama'    => $lama->shift->nama_shift ?? '—',
                ];
            }
        }

        $total = count($diff['diubah']) + count($diff['ditambah']) + count($diff['dihapus']);

        return response()->json([
            'status'  => true,
            'message' => $total === 0 ? 'Tidak ada perubahan.' : "{$total} perubahan ditemukan.",
            'data'    => [
                'diff'                => $diff,
                'total_perubahan'     => $total,
                'total_diubah'        => count($diff['diubah']),
                'total_ditambah'      => count($diff['ditambah']),
                'total_dihapus'       => count($diff['dihapus']),
                'total_tidak_berubah' => $jadwalLama->count() - count($diff['diubah']) - count($diff['dihapus']),
                'planning_lama'       => ['id_planning' => $planningLama->id_planning, 'versi' => $planningLama->versi, 'periode_label' => $planningLama->periode_label],
            ],
        ]);
    }

    // ── STORE — simpan planning baru ──────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'periode_bulan'          => 'required|integer|between:1,12',
            'periode_tahun'          => 'required|integer|min:2020|max:2100',
            'jadwal'                 => 'required|array|min:1',
            'jadwal.*.id_karyawan'   => 'required|integer',
            'jadwal.*.id_shift'      => 'required|integer',
            'jadwal.*.tanggal_kerja' => 'required|date',
        ]);

        $idPerusahaan = $this->getIdPerusahaan();
        $admin        = $this->authenticatedPengguna();

        $existing = PlanningKerja::where('id_perusahaan', $idPerusahaan)
            ->where('periode_bulan', $request->periode_bulan)
            ->where('periode_tahun', $request->periode_tahun)
            ->where('status', PlanningKerja::STATUS_AKTIF)->first();

        if ($existing) {
            return response()->json([
                'status'  => false,
                'message' => "Planning aktif untuk periode ini sudah ada. Gunakan Upload Ulang.",
                'data'    => ['id_planning' => $existing->id_planning],
            ], 422);
        }

        try {
            $planning = DB::transaction(function () use ($request, $idPerusahaan, $admin) {
                $versi = (PlanningKerja::where('id_perusahaan', $idPerusahaan)
                    ->where('periode_bulan', $request->periode_bulan)
                    ->where('periode_tahun', $request->periode_tahun)->max('versi') ?? 0) + 1;

                $planning = PlanningKerja::create([
                    'id_perusahaan' => $idPerusahaan,
                    'periode_bulan' => $request->periode_bulan,
                    'periode_tahun' => $request->periode_tahun,
                    'status'        => PlanningKerja::STATUS_AKTIF,
                    'versi'         => $versi,
                    'dibuat_oleh'   => $admin->id_pengguna,
                ]);

                JadwalKerja::insert(collect($request->jadwal)->map(fn($j) => [
                    'id_planning' => $planning->id_planning, 'id_karyawan' => $j['id_karyawan'],
                    'id_shift' => $j['id_shift'], 'tanggal_kerja' => $j['tanggal_kerja'],
                    'is_hari_libur' => false, 'created_at' => now(), 'updated_at' => now(),
                ])->toArray());

                return $planning;
            });

            $idPenggunaList = Karyawan::where('id_perusahaan', $idPerusahaan)
                ->whereIn('id_karyawan', collect($request->jadwal)->pluck('id_karyawan')->unique())
                ->pluck('id_pengguna')->toArray();
            NotifikasiService::planningBaru($idPenggunaList, $planning->periode_label, $planning->id_planning, $admin->id_pengguna);
            AuditLogService::catat(pengguna: $admin, jenis: AuditLog::JENIS_PLANNING, idReferensi: $planning->id_planning, aksi: AuditLog::AKSI_CREATE, catatan: "Planning {$planning->periode_label} v{$planning->versi} dibuat. ".count($request->jadwal)." jadwal.");

            return response()->json([
                'status'  => true,
                'message' => "Planning {$planning->periode_label} berhasil disimpan dengan ".count($request->jadwal)." jadwal.",
                'data'    => $this->formatPlanning($planning->loadCount('jadwal')),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Gagal simpan planning', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Gagal menyimpan planning.', 'data' => null], 500);
        }
    }

    // ── UPLOAD ULANG ──────────────────────────────────────────────────────────

    public function uploadUlang(Request $request, int $planning): JsonResponse
    {
        $request->validate([
            'jadwal'                 => 'required|array|min:1',
            'jadwal.*.id_karyawan'   => 'required|integer',
            'jadwal.*.id_shift'      => 'required|integer',
            'jadwal.*.tanggal_kerja' => 'required|date',
        ]);

        $planningLama = $this->findPlanning($planning);
        if (!$planningLama) return $this->notFound();

        $idPerusahaan = $this->getIdPerusahaan();
        $admin        = $this->authenticatedPengguna();

        try {
            $planningBaru = DB::transaction(function () use ($request, $planningLama, $idPerusahaan, $admin) {
                
                // ── FIX: hapus jadwal_kerja dari planning lama sebelum arsipkan 
                // Ini mencegah data duplikat jika filter planning aktif suatu saat
                // tidak diterapkan, dan menghemat storage jangka panjang.
                // CATATAN: jangan hapus jika ada absensi yang masih merujuk ke jadwal lama.
                // Cukup arsipkan planning, biarkan jadwal lama tetap ada tapi
                // tidak akan muncul karena filter status = aktif.
                // ─────────────────────────────────────────────────────────────────
                
                $planningLama->update(['status' => PlanningKerja::STATUS_DIPERBARUI]);
                
                $baru = PlanningKerja::create([
                    'id_perusahaan' => $idPerusahaan,
                    'periode_bulan' => $planningLama->periode_bulan,
                    'periode_tahun' => $planningLama->periode_tahun,
                    'status'        => PlanningKerja::STATUS_AKTIF,
                    'versi'         => $planningLama->versi + 1,
                    'dibuat_oleh'   => $admin->id_pengguna,
                ]);
                
                JadwalKerja::insert(collect($request->jadwal)->map(fn($j) => [
                    'id_planning' => $baru->id_planning,
                    'id_karyawan' => $j['id_karyawan'],
                    'id_shift'    => $j['id_shift'],
                    'tanggal_kerja' => $j['tanggal_kerja'],
                    'is_hari_libur' => false,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])->toArray());
                
                return $baru;
            });

            AuditLogService::catat(pengguna: $admin, jenis: AuditLog::JENIS_PLANNING, idReferensi: $planningBaru->id_planning, aksi: AuditLog::AKSI_UPLOAD, catatan: "Planning {$planningBaru->periode_label} diperbarui ke v{$planningBaru->versi}.");

            return response()->json([
                'status'  => true,
                'message' => "Planning {$planningBaru->periode_label} berhasil diperbarui ke versi {$planningBaru->versi}.",
                'data'    => $this->formatPlanning($planningBaru->loadCount('jadwal')),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal upload ulang planning', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Gagal memperbarui planning.', 'data' => null], 500);
        }
    }

    // ── UPDATE SATU SEL (grid interaktif) ────────────────────────────────────

    public function updateJadwal(Request $request, int $planning): JsonResponse
    {
        $request->validate([
            'id_karyawan'   => 'required|integer|exists:karyawan,id_karyawan',
            'tanggal_kerja' => 'required|date',
            'id_shift'      => 'nullable|integer|exists:shift,id_shift',
            'is_hari_libur' => 'required|boolean',
        ]);

        $planningData = $this->findPlanning($planning);
        if (!$planningData) return $this->notFound();
        if ($planningData->status !== PlanningKerja::STATUS_AKTIF) {
            return response()->json(['status' => false, 'message' => 'Hanya planning aktif yang dapat diubah.', 'data' => null], 422);
        }

        $jadwal = JadwalKerja::where('id_planning', $planning)
            ->where('id_karyawan', $request->id_karyawan)
            ->whereDate('tanggal_kerja', $request->tanggal_kerja)->first();

        if ($request->is_hari_libur) {
            if ($jadwal) {
                $jadwal->update(['is_hari_libur' => true, 'id_shift' => null]);
            } else {
                $jadwal = JadwalKerja::create(['id_planning' => $planning, 'id_karyawan' => $request->id_karyawan, 'id_shift' => null, 'tanggal_kerja' => $request->tanggal_kerja, 'is_hari_libur' => true]);
            }
            $aksi = 'Dijadikan hari libur';
        } else {
            if ($jadwal) {
                $jadwal->update(['id_shift' => $request->id_shift, 'is_hari_libur' => false]);
                $aksi = 'Shift diubah';
            } else {
                $jadwal = JadwalKerja::create(['id_planning' => $planning, 'id_karyawan' => $request->id_karyawan, 'id_shift' => $request->id_shift, 'tanggal_kerja' => $request->tanggal_kerja, 'is_hari_libur' => false]);
                $aksi = 'Jadwal ditambahkan';
            }
        }

        $karyawan = Karyawan::find($request->id_karyawan);
        AuditLogService::catat(pengguna: $this->authenticatedPengguna(), jenis: AuditLog::JENIS_PLANNING, idReferensi: $jadwal->id_jadwal, aksi: AuditLog::AKSI_UPDATE, catatan: "{$aksi} via grid: {$karyawan->nama_lengkap} — {$request->tanggal_kerja}");

        $jadwal->load('shift:id_shift,nama_shift,jam_masuk,jam_pulang');
        return response()->json([
            'status'  => true,
            'message' => "{$aksi} berhasil.",
            'data'    => [
                'id_jadwal'     => $jadwal->id_jadwal,
                'tanggal_kerja' => $jadwal->tanggal_kerja->format('Y-m-d'),
                'is_hari_libur' => $jadwal->is_hari_libur,
                'shift'         => $jadwal->shift ? ['id_shift' => $jadwal->shift->id_shift, 'nama_shift' => $jadwal->shift->nama_shift, 'jam_masuk' => substr($jadwal->shift->jam_masuk, 0, 5), 'jam_pulang' => substr($jadwal->shift->jam_pulang, 0, 5)] : null,
            ],
        ]);
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    private function findPlanning(int $id): ?PlanningKerja
    {
        return PlanningKerja::where('id_planning', $id)->where('id_perusahaan', $this->getIdPerusahaan())->first();
    }

    private function authenticatedPengguna(): Pengguna
    {
        $user = Auth::user();

        if (! $user instanceof Pengguna) {
            throw new AuthenticationException('Pengguna tidak terautentikasi.');
        }

        return $user;
    }

    private function getNamaBulan(int $b): string
    {
        return [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'][$b] ?? '?';
    }

    private function getColLetter(int $n): string
    {
        $l = '';
        while ($n > 0) { $n--; $l = chr(65 + ($n % 26)) . $l; $n = (int)($n / 26); }
        return $l;
    }

    private function formatPlanning(PlanningKerja $p): array
    {
        return ['id_planning' => $p->id_planning, 'periode_label' => $p->periode_label, 'periode_bulan' => $p->periode_bulan, 'periode_tahun' => $p->periode_tahun, 'status' => $p->status, 'versi' => $p->versi, 'jumlah_jadwal' => $p->jadwal_count ?? null, 'dibuat_oleh' => $p->dibuat_oleh, 'created_at' => $p->created_at->toDateTimeString()];
    }

    private function formatPlanningDetail(PlanningKerja $p): array
    {
        return array_merge($this->formatPlanning($p), ['jadwal' => $p->jadwal->map(fn($j) => ['id_jadwal' => $j->id_jadwal, 'tanggal_kerja' => $j->tanggal_kerja->format('Y-m-d'), 'is_hari_libur' => $j->is_hari_libur, 'karyawan' => $j->karyawan ? ['id_karyawan' => $j->karyawan->id_karyawan, 'nama_lengkap' => $j->karyawan->nama_lengkap, 'nomor_karyawan' => $j->karyawan->nomor_karyawan] : null, 'shift' => $j->shift ? ['id_shift' => $j->shift->id_shift, 'nama_shift' => $j->shift->nama_shift, 'jam_masuk' => substr($j->shift->jam_masuk,0,5), 'jam_pulang' => substr($j->shift->jam_pulang,0,5)] : null])->values()]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'Planning tidak ditemukan.', 'data' => null], 404);
    }
}
