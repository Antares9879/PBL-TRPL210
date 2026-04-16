/**
 * resources/js/admin-outsource/_planning-excel.js
 *
 * Modul khusus untuk semua operasi Excel di halaman Planning Kerja.
 * Diekstrak dari planning.js untuk menghilangkan duplikasi dan
 * mempermudah testing secara independen.
 *
 * Ekspor:
 *   parseExcelFile(file, bulan, tahun)  → ParseResult | null
 *   validateWithBackend(rows, bulan, tahun, sheetName) → ValidationResult
 *   getDiffFromBackend(idPlanningLama, jadwalBaru) → DiffResult
 *
 * Tipe data:
 *   ParseResult      = { rows, sheetName }
 *   ValidationResult = { valid, errors, warnings, ringkasan, planning_existing }
 *   DiffResult       = { diff, total_perubahan, ... }
 */

import * as XLSX from 'https://cdn.sheetjs.com/xlsx-0.20.0/package/xlsx.mjs';
import { apiFetch, toast } from './_utils.js';

// ════════════════════════════════════════════════════════════════════════════
//  PARSE EXCEL FILE
//  Membaca file .xlsx/.xls dan mengekstrak data jadwal per karyawan.
//
//  Return null jika file tidak valid atau tidak bisa dibaca.
//  Memanggil toast() untuk error yang perlu diinformasikan ke user.
// ════════════════════════════════════════════════════════════════════════════

/**
 * @param {File}   file   — file .xlsx/.xls dari input[type=file]
 * @param {number} bulan  — 1–12
 * @param {number} tahun  — contoh: 2026
 * @returns {Promise<{rows: Array, sheetName: string} | null>}
 */
export async function parseExcelFile(file, bulan, tahun) {
    // ── Validasi ekstensi ─────────────────────────────────────────────────
    if (!file.name.match(/\.(xlsx|xls)$/i)) {
        toast('File harus berformat .xlsx atau .xls', 'error');
        return null;
    }

    try {
        // ── Baca file sebagai ArrayBuffer ─────────────────────────────────
        const buffer = await file.arrayBuffer();
        const wb     = XLSX.read(buffer, { type: 'array' });

        // ── Pilih sheet yang tepat ────────────────────────────────────────
        // Prioritaskan sheet dengan nama "Planning_..." (dari template sistem)
        // Fallback ke sheet pertama jika tidak ditemukan
        const sheetName = wb.SheetNames.find(n => n.startsWith('Planning_'))
            ?? wb.SheetNames[0];
        const ws = wb.Sheets[sheetName];

        if (!ws) {
            toast('Sheet planning tidak ditemukan di file Excel ini.', 'error');
            return null;
        }

        // ── Konversi ke array 2D ──────────────────────────────────────────
        // defval: '' agar sel kosong tidak jadi undefined
        const raw = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });

        // ── Ekstrak mapping kolom → tanggal ───────────────────────────────
        // Struktur template:
        //   Baris 1 (index 0): judul
        //   Baris 2 (index 1): header (kolom A–D = info, kolom E+ = tanggal)
        //   Baris 3+ (index 2+): data karyawan
        const colTanggal = _buildColTanggalMap(raw[1] ?? [], bulan, tahun);

        if (Object.keys(colTanggal).length === 0) {
            toast(
                'Tidak ditemukan kolom tanggal di file ini. ' +
                'Pastikan menggunakan template yang didownload dari sistem.',
                'error'
            );
            return null;
        }

        // ── Parse baris data karyawan ─────────────────────────────────────
        const rows = _parseDataRows(raw, colTanggal);

        if (rows.length === 0) {
            toast('Tidak ada data karyawan yang dapat dibaca dari file ini.', 'error');
            return null;
        }

        return { rows, sheetName };

    } catch (err) {
        console.error('[ExcelParser] parseExcelFile error:', err);
        toast('Gagal membaca file Excel. Pastikan file tidak rusak.', 'error');
        return null;
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  VALIDATE WITH BACKEND
//  Mengirim data hasil parse ke endpoint upload-excel untuk validasi server-side:
//    - karyawan valid & milik perusahaan
//    - kode shift dikenal (P/S/M/N)
//    - tidak ada duplikat tanggal per karyawan
//    - tanggal sesuai periode
// ════════════════════════════════════════════════════════════════════════════

/**
 * @param {Array}  rows      — hasil dari parseExcelFile().rows
 * @param {number} bulan
 * @param {number} tahun
 * @param {string} sheetName — untuk sanity check nama sheet vs periode
 * @returns {Promise<object>} json.data dari backend
 */
export async function validateWithBackend(rows, bulan, tahun, sheetName) {
    const res  = await apiFetch('/api/admin/planning/upload-excel', {
        method: 'POST',
        body: JSON.stringify({ periode_bulan: bulan, periode_tahun: tahun, sheet_name: sheetName, rows }),
    });
    const json = await res.json();

    // Backend return { status, message, data: { valid, errors, warnings, ringkasan, planning_existing } }
    // Lempar error jika HTTP gagal (misalnya 403, 500) agar ditangani caller
    if (!res.ok && !json.data) {
        throw new Error(json.message ?? `HTTP ${res.status}`);
    }

    return json.data;
}

// ════════════════════════════════════════════════════════════════════════════
//  GET DIFF FROM BACKEND
//  Membandingkan jadwal baru dengan planning lama untuk fitur Upload Ulang (F09).
//  Mengembalikan kategori perubahan: diubah / ditambah / dihapus.
// ════════════════════════════════════════════════════════════════════════════

/**
 * @param {number} idPlanningLama
 * @param {Array}  jadwalBaru — array { id_karyawan, tanggal_kerja, id_shift }
 * @returns {Promise<object>} json.data dari backend
 */
export async function getDiffFromBackend(idPlanningLama, jadwalBaru) {
    const res  = await apiFetch('/api/admin/planning/preview-diff', {
        method: 'POST',
        body: JSON.stringify({ id_planning_lama: idPlanningLama, jadwal_baru: jadwalBaru }),
    });
    const json = await res.json();

    if (!res.ok && !json.data) {
        throw new Error(json.message ?? `HTTP ${res.status}`);
    }

    return json.data;
}

// ════════════════════════════════════════════════════════════════════════════
//  PRIVATE HELPERS
//  Tidak diekspos — hanya digunakan secara internal oleh modul ini.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Membangun map: indeks kolom → string tanggal 'YYYY-MM-DD'
 * dari baris header spreadsheet.
 *
 * Kolom A–D (index 0–3) adalah kolom info karyawan, diabaikan.
 * Kolom E ke kanan (index 4+) adalah kolom tanggal.
 *
 * @param {Array}  headerRow — raw[1], baris header dari sheet
 * @param {number} bulan
 * @param {number} tahun
 * @returns {Object} { [colIndex]: 'YYYY-MM-DD' }
 */
function _buildColTanggalMap(headerRow, bulan, tahun) {
    const colTanggal = {};
    const bulanPad   = String(bulan).padStart(2, '0');

    for (let c = 4; c < headerRow.length; c++) {
        const val = headerRow[c];
        if (val === '' || val === undefined) continue;

        // Sel header tanggal bisa berisi "1\nSen", "15\nRab", dll
        // Ambil hanya angka di bagian pertama sebelum newline
        const num = parseInt(String(val).split('\n')[0]);

        if (!isNaN(num) && num >= 1 && num <= 31) {
            const tglPad     = String(num).padStart(2, '0');
            colTanggal[c]    = `${tahun}-${bulanPad}-${tglPad}`;
        }
    }

    return colTanggal;
}

/**
 * Mem-parse baris data karyawan dari spreadsheet.
 * Menghasilkan array siap kirim ke endpoint upload-excel.
 *
 * Format per row:
 *   Kolom A (index 0): nama karyawan  (diabaikan — hanya untuk keterbacaan)
 *   Kolom B (index 1): id_karyawan    (kunci utama)
 *   Kolom C (index 2): nama departemen (diabaikan)
 *   Kolom D (index 3): posisi          (diabaikan)
 *   Kolom E+ (index 4+): kode shift per tanggal
 *
 * @param {Array}  raw        — seluruh baris dari sheet_to_json
 * @param {Object} colTanggal — hasil _buildColTanggalMap()
 * @returns {Array<{ id_karyawan: number, jadwal: Object }>}
 */
function _parseDataRows(raw, colTanggal) {
    const rows = [];

    // Data mulai baris ke-3 (index 2), lewati baris judul (0) dan header (1)
    for (let r = 2; r < raw.length; r++) {
        const row = raw[r];

        // Skip baris kosong atau tanpa ID karyawan
        if (!row || !row[1]) continue;

        const idKaryawan = parseInt(row[1]);
        if (isNaN(idKaryawan)) continue;

        // Kumpulkan jadwal: hanya sel yang terisi (non-kosong, non-strip)
        const jadwal = {};
        for (const [colIdx, tglStr] of Object.entries(colTanggal)) {
            const val = String(row[colIdx] ?? '').trim();
            if (val && val !== '-') {
                jadwal[tglStr] = val;
            }
        }

        rows.push({ id_karyawan: idKaryawan, jadwal });
    }

    return rows;
}