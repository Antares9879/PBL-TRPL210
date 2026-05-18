<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\GenerateRekapRequest;
use App\Models\AuditLog;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\PerusahaanOutsource;
use App\Models\RekapBulanan;
use App\Services\AuditLogService;
use App\Services\RekapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * RekapApiController — HR Ecogreen
 *
 * Memungkinkan HR menarik, melihat preview, dan mengunduh rekap absensi bulanan.
 * Data yang dihasilkan bersifat non-payroll (satuan menit, tanpa rupiah).
 *
 * Business rules (UC-15):
 *   - HR bisa preview sebelum unduh.
 *   - Unduh dalam format Excel (.xlsx).
 *   - Jika ada dokumen belum lengkap, sistem memberi peringatan tapi tetap izinkan unduh.
 *   - HR bisa generate rekap (simpan ke DB) dan menetapkan status Final.
 *   - Rekap berstatus Final tidak bisa di-generate ulang.
 *
 * Endpoints:
 *   GET  /api/hr/rekap                     → index()       — daftar rekap tersimpan (paginasi)
 *   GET  /api/hr/rekap/preview             → preview()     — data real-time sebelum generate
 *   POST /api/hr/rekap/generate            → generate()    — simpan rekap ke DB
 *   POST /api/hr/rekap/{id}/final          → tetapkanFinal() — kunci rekap sebagai Final
 *   GET  /api/hr/rekap/unduh               → unduh()       — download file Excel
 *   GET  /api/hr/rekap/cek-dokumen         → cekDokumen()  — cek status dokumen sebelum Final
 */
class RekapApiController extends Controller
{
    public function __construct(
        private readonly RekapService $rekapService,
    ) {}

    // ════════════════════════════════════════════════════════════════════════
    //  INDEX — Daftar rekap yang tersimpan di DB
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/rekap
     *
     * Daftar rekap bulanan yang sudah digenerate dan tersimpan di DB.
     * Filter: bulan, tahun, id_departemen, id_perusahaan, status_rekap
     */
    public function index(Request $request): JsonResponse
    {
        $query = RekapBulanan::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan',
            'karyawan.departemen:id_departemen,nama_departemen',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'pembuat:id_pengguna,nama_lengkap',
        ]);

        if ($request->filled('bulan')) {
            $query->where('periode_bulan', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->where('periode_tahun', $request->tahun);
        }
        if ($request->filled('status_rekap')) {
            $query->where('status_rekap', $request->status_rekap);
        }
        if ($request->filled('id_departemen')) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_departemen', $request->id_departemen));
        }
        if ($request->filled('id_perusahaan')) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $request->id_perusahaan));
        }

        $data = $query
            ->orderBy('periode_tahun', 'desc')
            ->orderBy('periode_bulan', 'desc')
            ->orderBy('id_rekap')
            ->paginate(20);

        $data->getCollection()->transform(fn($r) => $this->formatRekap($r));

        return response()->json([
            'status'  => true,
            'message' => 'Data rekap berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PREVIEW — Data real-time sebelum generate / unduh
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/rekap/preview
     *
     * Menampilkan data rekap real-time tanpa menyimpan ke DB.
     * Digunakan untuk preview tabel sebelum HR memutuskan untuk generate atau unduh.
     *
     * Query params: bulan (required), tahun (required), id_departemen, id_perusahaan
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $bulan        = (int) $request->bulan;
        $tahun        = (int) $request->tahun;
        $idDepartemen = $request->filled('id_departemen') ? (int) $request->id_departemen : null;
        $idPerusahaan = $request->filled('id_perusahaan') ? (int) $request->id_perusahaan : null;

        $data = $this->rekapService->getDataPreview($bulan, $tahun, $idDepartemen, $idPerusahaan);

        // Cek status dokumen — warning jika ada yang belum lengkap
        $statusDokumen = $this->rekapService->cekStatusDokumen($bulan, $tahun, $idDepartemen, $idPerusahaan);

        // Hitung total agregat
        $totalMenitNormal  = $data->sum('total_menit_normal');
        $totalMenitLembur  = $data->sum('total_menit_lembur');
        $totalHariHadir    = $data->sum('total_hari_hadir');
        $totalHariIzin     = $data->sum('total_hari_izin');
        $totalHariAlpa     = $data->sum('total_hari_alpa');

        // Hitung nama bulan untuk label
        $namaBulan = $this->getNamaBulan($bulan);

        return response()->json([
            'status'  => true,
            'message' => 'Preview rekap berhasil dimuat.',
            'data'    => [
                'periode'       => [
                    'bulan'       => $bulan,
                    'tahun'       => $tahun,
                    'label'       => "{$namaBulan} {$tahun}",
                ],
                'karyawan'      => $data->values(),
                'total_karyawan'=> $data->count(),
                'agregat'       => [
                    'total_menit_normal' => $totalMenitNormal,
                    'total_menit_lembur' => $totalMenitLembur,
                    'total_hari_hadir'   => $totalHariHadir,
                    'total_hari_izin'    => $totalHariIzin,
                    'total_hari_alpa'    => $totalHariAlpa,
                ],
                'peringatan_dokumen' => $statusDokumen['ada_tidak_lengkap'] ? [
                    'ada_masalah'   => true,
                    'jumlah'        => $statusDokumen['jumlah'],
                    'pesan'         => "Terdapat {$statusDokumen['jumlah']} pengajuan izin dengan dokumen belum lengkap/terverifikasi. Data tetap bisa diunduh namun mungkin tidak final.",
                    'detail'        => $statusDokumen['detail'],
                ] : ['ada_masalah' => false],
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  GENERATE — Simpan rekap ke tabel rekap_bulanan
    // ════════════════════════════════════════════════════════════════════════

    /**
     * POST /api/hr/rekap/generate
     *
     * Generate dan simpan rekap ke DB untuk seluruh karyawan pada periode.
     * Menggunakan RekapService::generate() yang idempotent (aman dipanggil ulang).
     * Rekap berstatus Final tidak bisa di-generate ulang.
     */
    public function generate(GenerateRekapRequest $request): JsonResponse
    {
        $hr           = Auth::user();
        $bulan        = (int) $request->bulan;
        $tahun        = (int) $request->tahun;
        $idDepartemen = $request->filled('id_departemen') ? (int) $request->id_departemen : null;
        $idPerusahaan = $request->filled('id_perusahaan') ? (int) $request->id_perusahaan : null;

        $hasil = $this->rekapService->generate(
            bulan:        $bulan,
            tahun:        $tahun,
            idDepartemen: $idDepartemen,
            idPerusahaan: $idPerusahaan,
            idPembuatHr:  $hr->id_pengguna,
        );

        // Catat audit log
        AuditLogService::catat(
            pengguna:    $hr,
            jenis:       AuditLog::JENIS_MASTER_DATA,
            idReferensi: $hr->id_pengguna,
            aksi:        AuditLog::AKSI_CREATE,
            catatan:     "Generate rekap {$this->getNamaBulan($bulan)} {$tahun}: {$hasil['berhasil']} berhasil, {$hasil['gagal']} gagal.",
        );

        $pesanStatus = $hasil['gagal'] === 0
            ? "Rekap berhasil digenerate untuk {$hasil['berhasil']} karyawan."
            : "Rekap digenerate: {$hasil['berhasil']} berhasil, {$hasil['gagal']} gagal.";

        return response()->json([
            'status'  => $hasil['gagal'] === 0,
            'message' => $pesanStatus,
            'data'    => [
                'berhasil' => $hasil['berhasil'],
                'gagal'    => $hasil['gagal'],
                'errors'   => $hasil['errors'],
            ],
        ], $hasil['gagal'] === 0 ? 200 : 207);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  TETAPKAN FINAL — Kunci rekap agar tidak bisa diubah
    // ════════════════════════════════════════════════════════════════════════

    /**
     * POST /api/hr/rekap/{id}/final
     *
     * Tetapkan satu rekap sebagai Final.
     * Guard: tidak bisa ditetapkan Final jika masih ada dokumen tidak lengkap
     *        (kecuali HR menggunakan parameter force=true).
     */
    public function tetapkanFinal(Request $request, int $id): JsonResponse
    {
        $hr    = Auth::user();
        $force = $request->boolean('force', false);

        $rekap = RekapBulanan::with('karyawan:id_karyawan,nama_lengkap,id_departemen,id_perusahaan')
            ->find($id);

        if (! $rekap) {
            return response()->json([
                'status'  => false,
                'message' => 'Data rekap tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if ($rekap->status_rekap === RekapBulanan::STATUS_FINAL) {
            return response()->json([
                'status'  => false,
                'message' => 'Rekap ini sudah berstatus Final.',
                'data'    => null,
            ], 422);
        }

        // Cek status dokumen untuk karyawan ini saja
        $statusDokumen = $this->rekapService->cekStatusDokumen(
            bulan:        $rekap->periode_bulan,
            tahun:        $rekap->periode_tahun,
            idDepartemen: $rekap->karyawan?->id_departemen,
        );

        if ($statusDokumen['ada_tidak_lengkap'] && ! $force) {
            return response()->json([
                'status'  => false,
                'message' => 'Rekap tidak dapat ditetapkan Final karena masih ada dokumen yang belum lengkap/terverifikasi. Gunakan parameter force=true untuk tetap menetapkan Final.',
                'data'    => [
                    'jumlah_dokumen_bermasalah' => $statusDokumen['jumlah'],
                    'detail'                    => $statusDokumen['detail'],
                ],
            ], 422);
        }

        try {
            $rekap = $this->rekapService->tetapkanFinal($id, $hr->id_pengguna);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        }

        AuditLogService::catat(
            pengguna:    $hr,
            jenis:       AuditLog::JENIS_MASTER_DATA,
            idReferensi: $rekap->id_rekap,
            aksi:        AuditLog::AKSI_UPDATE,
            catatan:     "Rekap ditetapkan Final: {$rekap->karyawan?->nama_lengkap} — {$this->getNamaBulan($rekap->periode_bulan)} {$rekap->periode_tahun}",
        );

        return response()->json([
            'status'  => true,
            'message' => "Rekap {$rekap->karyawan?->nama_lengkap} berhasil ditetapkan sebagai Final.",
            'data'    => $this->formatRekap($rekap->fresh()->load([
                'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan',
                'karyawan.departemen:id_departemen,nama_departemen',
                'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
                'pembuat:id_pengguna,nama_lengkap',
            ])),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  UNDUH — Export rekap ke Excel
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/rekap/unduh
     *
     * Mengunduh rekap absensi bulanan dalam format Excel (.xlsx).
     * Menggunakan data real-time (tidak perlu generate dulu ke DB).
     * Memberi peringatan jika ada dokumen belum final, tapi tetap mengizinkan unduh.
     *
     * Query params: bulan (required), tahun (required), id_departemen, id_perusahaan
     */
    public function unduh(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $bulan        = (int) $request->bulan;
        $tahun        = (int) $request->tahun;
        $idDepartemen = $request->filled('id_departemen') ? (int) $request->id_departemen : null;
        $idPerusahaan = $request->filled('id_perusahaan') ? (int) $request->id_perusahaan : null;
        $namaBulan    = $this->getNamaBulan($bulan);

        // Ambil data rekap
        $data = $this->rekapService->getDataPreview($bulan, $tahun, $idDepartemen, $idPerusahaan);

        if ($data->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Tidak ada data karyawan untuk periode yang dipilih.',
                'data'    => null,
            ], 422);
        }

        // Build label filter untuk header Excel
        $labelFilter = '';
        if ($idDepartemen) {
            $dept = Departemen::find($idDepartemen);
            $labelFilter .= $dept ? " — Departemen: {$dept->nama_departemen}" : '';
        }
        if ($idPerusahaan) {
            $peru = PerusahaanOutsource::find($idPerusahaan);
            $labelFilter .= $peru ? " — Perusahaan: {$peru->nama_perusahaan}" : '';
        }

        // Catat audit log unduhan
        $hr = Auth::user();
        AuditLogService::catat(
            pengguna:    $hr,
            jenis:       AuditLog::JENIS_MASTER_DATA,
            idReferensi: $hr->id_pengguna,
            aksi:        AuditLog::AKSI_UPLOAD,
            catatan:     "Unduh rekap {$namaBulan} {$tahun}{$labelFilter} ({$data->count()} karyawan)",
        );

        // Build Excel
        $spreadsheet = $this->buildExcel($data, $bulan, $tahun, $namaBulan, $labelFilter);
        $namaFile    = "Rekap_Absensi_{$namaBulan}_{$tahun}.xlsx";
        $writer      = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $namaFile,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CEK DOKUMEN — Status dokumen sebelum penetapan Final
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/rekap/cek-dokumen
     *
     * Cek apakah masih ada dokumen belum lengkap untuk periode tertentu.
     * Digunakan sebagai validasi sebelum HR menetapkan rekap sebagai Final.
     */
    public function cekDokumen(Request $request): JsonResponse
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $bulan        = (int) $request->bulan;
        $tahun        = (int) $request->tahun;
        $idDepartemen = $request->filled('id_departemen') ? (int) $request->id_departemen : null;
        $idPerusahaan = $request->filled('id_perusahaan') ? (int) $request->id_perusahaan : null;

        $hasil = $this->rekapService->cekStatusDokumen($bulan, $tahun, $idDepartemen, $idPerusahaan);

        return response()->json([
            'status'  => true,
            'message' => $hasil['ada_tidak_lengkap']
                ? "Terdapat {$hasil['jumlah']} dokumen yang belum lengkap."
                : 'Semua dokumen sudah lengkap dan terverifikasi.',
            'data'    => $hasil,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Build Excel
    // ════════════════════════════════════════════════════════════════════════

    private function buildExcel(
        \Illuminate\Support\Collection $data,
        int $bulan,
        int $tahun,
        string $namaBulan,
        string $labelFilter,
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Rekap_{$namaBulan}_{$tahun}");

        // ── Style definitions ─────────────────────────────────────────────
        $sTitle = [
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D4726']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sHeader = [
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A6E1A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AADDAA']]],
        ];
        $sData = [
            'font'    => ['size' => 9],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
        $sDataCenter = array_merge($sData, [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sDataFinal = array_merge($sData, [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
        ]);

        // ── Row 1–2: Judul ────────────────────────────────────────────────
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', "REKAP ABSENSI BULANAN — {$namaBulan} {$tahun}{$labelFilter}");
        $sheet->getStyle('A1')->applyFromArray($sTitle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', "Dicetak pada: " . now()->format('d/m/Y H:i') . " | Data non-payroll (menit kerja, bukan upah)");
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 8, 'color' => ['rgb' => '666666']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F9F0']],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // ── Row 3: Header ─────────────────────────────────────────────────
        $headers = [
            'A' => ['No.',               5],
            'B' => ['Nama Karyawan',     30],
            'C' => ['No. Karyawan',      14],
            'D' => ['Posisi',            18],
            'E' => ['Departemen',        20],
            'F' => ['Perusahaan',        22],
            'G' => ['Hari\nKerja',        8],
            'H' => ['Hari\nHadir',        8],
            'I' => ['Hari\nIzin',         8],
            'J' => ['Hari\nAlpa',         8],
            'K' => ['Menit\nNormal',     10],
            'L' => ['Menit\nLembur',     10],
            'M' => ['Menit\nTelat',      10],
            'N' => ['Menit\nPlg Cepat',  10],
            'O' => ['Status\nRekap',     12],
            'P' => ['Keterangan',        20],
        ];

        foreach ($headers as $col => [$label, $width]) {
            $sheet->setCellValue("{$col}3", $label);
            $sheet->getStyle("{$col}3")->applyFromArray($sHeader);
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getRowDimension(3)->setRowHeight(32);

        // ── Row 4+: Data ──────────────────────────────────────────────────
        $row = 4;
        foreach ($data as $idx => $k) {
            $isFinal  = ($k['status_rekap'] === 'final');
            $styleRow = $isFinal ? $sDataFinal : $sData;

            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $k['nama_lengkap']);
            $sheet->setCellValue("C{$row}", $k['nomor_karyawan']);
            $sheet->setCellValue("D{$row}", $k['posisi']);
            $sheet->setCellValue("E{$row}", $k['departemen']);
            $sheet->setCellValue("F{$row}", $k['perusahaan']);
            $sheet->setCellValue("G{$row}", $k['total_hari_kerja']);
            $sheet->setCellValue("H{$row}", $k['total_hari_hadir']);
            $sheet->setCellValue("I{$row}", $k['total_hari_izin']);
            $sheet->setCellValue("J{$row}", $k['total_hari_alpa']);
            $sheet->setCellValue("K{$row}", $k['total_menit_normal']);
            $sheet->setCellValue("L{$row}", $k['total_menit_lembur']);
            $sheet->setCellValue("M{$row}", $k['total_menit_telat']);
            $sheet->setCellValue("N{$row}", $k['total_menit_pulang_cepat']);
            $sheet->setCellValue("O{$row}", match($k['status_rekap']) {
                'final'            => 'Final',
                'draft'            => 'Draft',
                'belum_digenerate' => 'Real-time',
                default            => '-',
            });
            $sheet->setCellValue("P{$row}", $isFinal ? 'Data telah dikunci' : '');

            $sheet->getStyle("A{$row}:P{$row}")->applyFromArray($styleRow);
            $sheet->getStyle("G{$row}:P{$row}")->applyFromArray($sDataCenter);
            $sheet->getRowDimension($row)->setRowHeight(16);
            $row++;
        }

        // ── Baris total ───────────────────────────────────────────────────
        $totalRow = $row;
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->setCellValue("G{$totalRow}", $data->sum('total_hari_kerja'));
        $sheet->setCellValue("H{$totalRow}", $data->sum('total_hari_hadir'));
        $sheet->setCellValue("I{$totalRow}", $data->sum('total_hari_izin'));
        $sheet->setCellValue("J{$totalRow}", $data->sum('total_hari_alpa'));
        $sheet->setCellValue("K{$totalRow}", $data->sum('total_menit_normal'));
        $sheet->setCellValue("L{$totalRow}", $data->sum('total_menit_lembur'));
        $sheet->setCellValue("M{$totalRow}", $data->sum('total_menit_telat'));
        $sheet->setCellValue("N{$totalRow}", $data->sum('total_menit_pulang_cepat'));

        $sheet->getStyle("A{$totalRow}:P{$totalRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF9C3']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A{$totalRow}:B{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("A{$totalRow}")->getFont()->setSize(10);

        // Freeze panes
        $sheet->freezePane('G4');

        return $spreadsheet;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Helpers
    // ════════════════════════════════════════════════════════════════════════

    private function getNamaBulan(int $bulan): string
    {
        return [
            1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
            4  => 'April',    5  => 'Mei',       6  => 'Juni',
            7  => 'Juli',     8  => 'Agustus',   9  => 'September',
            10 => 'Oktober',  11 => 'November',  12 => 'Desember',
        ][$bulan] ?? '?';
    }

    private function formatRekap(RekapBulanan $r): array
    {
        return [
            'id_rekap'                => $r->id_rekap,
            'periode_bulan'           => $r->periode_bulan,
            'periode_tahun'           => $r->periode_tahun,
            'periode_label'           => $this->getNamaBulan($r->periode_bulan) . ' ' . $r->periode_tahun,
            'karyawan'                => $r->karyawan ? [
                'id_karyawan'    => $r->karyawan->id_karyawan,
                'nama_lengkap'   => $r->karyawan->nama_lengkap,
                'nomor_karyawan' => $r->karyawan->nomor_karyawan,
                'departemen'     => $r->karyawan->departemen?->nama_departemen,
                'perusahaan'     => $r->karyawan->perusahaan?->nama_perusahaan,
            ] : null,
            'total_hari_kerja'        => $r->total_hari_kerja,
            'total_hari_hadir'        => $r->total_hari_hadir,
            'total_hari_izin'         => $r->total_hari_izin,
            'total_hari_alpa'         => $r->total_hari_alpa,
            'total_menit_normal'      => $r->total_menit_normal,
            'total_menit_lembur'      => $r->total_menit_lembur,
            'total_menit_telat'       => $r->total_menit_telat,
            'total_menit_pulang_cepat'=> $r->total_menit_pulang_cepat,
            'status_rekap'            => $r->status_rekap,
            'dibuat_oleh'             => $r->pembuat?->nama_lengkap,
            'ditetapkan_pada'         => $r->ditetapkan_pada?->toDateTimeString(),
            'created_at'              => $r->created_at?->toDateTimeString(),
            'updated_at'              => $r->updated_at?->toDateTimeString(),
        ];
    }
}
