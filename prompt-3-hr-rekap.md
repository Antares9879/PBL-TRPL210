# Prompt 3 — Halaman Rekap Absensi HR (F15)

## Konteks Proyek

Kamu adalah Laravel 12 Fullstack Developer yang bekerja pada proyek **E-Outsourcing PT Ecogreen Oleochemicals Batam Plant** (PBL-TRPL210). Baca dan pahami file-file berikut sebelum mulai:

- `STRUKTUR-FOLDER.md` — panduan wajib struktur folder, konvensi penamaan, dan arsitektur proyek
- `resources/views/layouts/app.blade.php` — layout utama yang sudah ada
- `resources/views/hr/dashboard.blade.php` — halaman HR yang sudah dibuat (ambil pola sidebar-nya)
- `resources/css/hr.css` — CSS HR yang sudah ada (jangan buat ulang, hanya tambahkan class baru)
- `app/Http/Controllers/Api/HR/RekapApiController.php` — controller API yang akan di-consume
- `app/Services/RekapService.php` — referensi logika bisnis rekap

Ikuti **semua** aturan di `STRUKTUR-FOLDER.md` tanpa pengecualian.

---

## Tugas

Buat halaman **Rekap Absensi HR** (F15) dengan tiga file:

1. `resources/views/hr/rekap.blade.php`
2. `resources/js/hr/rekap.js`
3. CSS tambahan dimasukkan ke `resources/css/hr.css` yang sudah ada

Tambahkan route web di `routes/web.php`:
```php
Route::get('rekap', [HR\PageController::class, 'rekap'])->name('rekap');
```

---

## Spesifikasi Blade (`hr/rekap.blade.php`)

### Struktur Layout
```
+--------------------------------------------------+
| SIDEBAR (sama dengan halaman HR lain)            |
+--------+-----------------------------------------+
| SIDEBAR | KONTEN UTAMA                            |
|         | Header: "Rekap Absensi Bulanan"         |
|         +-----------------------------------------+
|         | PANEL FILTER & AKSI                     |
|         | [Bulan] [Tahun] [Departemen] [Perusahaan]|
|         | [Terapkan] [Preview] [Generate] [Unduh] |
|         +-----------------------------------------+
|         | PANEL PERINGATAN DOKUMEN (conditional)  |
|         | (muncul jika ada dokumen belum lengkap) |
|         +-----------------------------------------+
|         | TABEL PREVIEW / REKAP                   |
|         | (data real-time atau dari DB)           |
|         +-----------------------------------------+
|         | PANEL AKSI FINAL                        |
|         | Tombol bulk "Tetapkan Final"            |
+--------------------------------------------------+
```

### Elemen HTML Shell yang Wajib Ada
```html
<!-- Filter & Aksi -->
<select id="filter-bulan-rekap"></select>
<select id="filter-tahun-rekap"></select>
<select id="filter-departemen-rekap"></select>
<select id="filter-perusahaan-rekap"></select>
<button id="btn-preview-rekap">Preview</button>
<button id="btn-generate-rekap">Generate & Simpan</button>
<button id="btn-unduh-rekap">Unduh Excel</button>

<!-- Panel peringatan dokumen belum lengkap -->
<div id="panel-peringatan-dokumen" style="display:none;">
  <div id="peringatan-dokumen-isi"></div>
  <a href="/hr/dokumen" id="btn-ke-verifikasi">Pergi ke Verifikasi Dokumen</a>
</div>

<!-- Info agregat setelah preview -->
<div id="panel-agregat-rekap" style="display:none;">
  <div id="card-total-karyawan-rekap"></div>
  <div id="card-total-hadir"></div>
  <div id="card-total-lembur"></div>
  <div id="card-total-alpa"></div>
</div>

<!-- Tabel rekap -->
<div id="tabel-rekap"></div>
<div id="paginasi-rekap"></div>

<!-- Panel aksi final (muncul setelah preview/generate) -->
<div id="panel-aksi-final" style="display:none;">
  <p id="info-status-final"></p>
  <button id="btn-final-semua">Tetapkan Semua sebagai Final</button>
</div>

<!-- Modal konfirmasi generate -->
<div id="modal-konfirmasi-generate" class="hr-modal" style="display:none;">
  <div class="hr-modal-content">
    <div id="modal-generate-body"></div>
    <div class="hr-modal-footer">
      <button id="btn-batal-generate">Batal</button>
      <button id="btn-submit-generate" class="hr-btn-primary">Ya, Generate</button>
    </div>
  </div>
</div>

<!-- Modal konfirmasi final -->
<div id="modal-konfirmasi-final" class="hr-modal" style="display:none;">
  <div class="hr-modal-content">
    <div id="modal-final-body"></div>
    <div class="hr-modal-footer">
      <button id="btn-batal-final">Batal</button>
      <button id="btn-submit-final" class="hr-btn-primary">Ya, Tetapkan Final</button>
    </div>
  </div>
</div>

<!-- Toast notifikasi -->
<div id="hr-toast" class="hr-toast" style="display:none;"></div>
```

---

## Spesifikasi JS (`hr/rekap.js`)

### Endpoint API yang Digunakan
```
GET  /api/hr/rekap/preview?bulan=&tahun=&id_departemen=&id_perusahaan=
GET  /api/hr/rekap/cek-dokumen?bulan=&tahun=&id_departemen=&id_perusahaan=
GET  /api/hr/rekap?bulan=&tahun=&id_departemen=&id_perusahaan=&status_rekap=
POST /api/hr/rekap/generate                  — body: { bulan, tahun, id_departemen, id_perusahaan }
POST /api/hr/rekap/{id}/final
GET  /api/hr/rekap/unduh?bulan=&tahun=...   — trigger download file
GET  /api/hr/dashboard/filter-options       — untuk dropdown departemen & perusahaan
```

### Alur Kerja JS
1. Load → inisialisasi filter (bulan default bulan ini, tahun default tahun ini)
2. `loadFilterOptions()` → isi dropdown departemen & perusahaan
3. Klik **Preview**:
   - Fetch `cek-dokumen` → jika ada masalah tampilkan `#panel-peringatan-dokumen` dengan detail
   - Fetch `rekap/preview` → render tabel preview + panel agregat
   - Tampilkan `#panel-aksi-final`
4. Klik **Generate & Simpan**:
   - Tampilkan modal konfirmasi generate
   - Setelah konfirmasi → `POST /api/hr/rekap/generate`
   - Jika berhasil → tampilkan toast sukses, refresh tabel dengan data dari DB (`GET /api/hr/rekap`)
5. Klik **Unduh Excel**:
   - Buka URL download via `window.location.href = '/api/hr/rekap/unduh?...'`
   - Ini akan trigger browser download otomatis
6. Klik **Tetapkan Semua sebagai Final**:
   - Kumpulkan semua `id_rekap` yang status-nya `draft` dari tabel
   - Tampilkan modal konfirmasi final dengan jumlah rekap
   - Loop `POST /api/hr/rekap/{id}/final` untuk setiap id_rekap
   - Tampilkan progress bar di modal saat proses berjalan
   - Setelah semua selesai → refresh tabel

### Render Tabel Rekap
Kolom:
| No | Nama Karyawan | No. Karyawan | Departemen | Perusahaan | Hari Kerja | Hari Hadir | Hari Izin | Hari Alpa | Menit Normal | Menit Lembur | Menit Telat | Status | Aksi |

- Kolom **Status**: badge `Draft` (abu) atau `Final` (hijau) atau `Real-time` (biru)
- Kolom **Aksi**: tombol `Tetapkan Final` (hanya muncul jika status = `draft`)
- Baris dengan status `final` diberi background hijau sangat muda
- Baris dengan `total_hari_alpa > 3` diberi background merah sangat muda sebagai perhatian HR

### Panel Peringatan Dokumen
Jika `cek-dokumen` mengembalikan `ada_tidak_lengkap: true`:
```html
<!-- Render oleh JS di #peringatan-dokumen-isi -->
⚠️ Perhatian: Terdapat {N} pengajuan izin dengan dokumen belum lengkap.
Rekap dapat diunduh, namun beberapa data mungkin belum final.

Detail:
• [Nama Karyawan] — [Tanggal Izin] — Status: [status_dokumen]
• ...

[Tombol: Pergi ke Verifikasi Dokumen →]
```

### Panel Agregat
Tampilkan 4 stat cards mini setelah preview/generate berhasil:
- Total Karyawan dalam rekap
- Total Hari Hadir (sum dari semua karyawan)
- Total Menit Lembur Resmi (sum)
- Total Hari Alpa (sum)

### Proses Tetapkan Final dengan Progress
Saat bulk tetapkan final, modal menampilkan progress:
```javascript
async function tetapkanFinalSemua(listIdRekap) {
  // Tampilkan progress bar di modal
  // Loop satu per satu
  for (let i = 0; i < listIdRekap.length; i++) {
    await fetch(`/api/hr/rekap/${listIdRekap[i]}/final`, { method: 'POST', ... });
    updateProgressBar((i + 1) / listIdRekap.length * 100);
  }
  // Setelah selesai: tutup modal, tampilkan toast, refresh tabel
}
```

---

## CSS Tambahan untuk `hr.css`

```css
/* Panel peringatan */
.hr-panel-peringatan {
  /* border kiri amber/kuning, background kuning muda */
}

/* Tabel rekap — baris status khusus */
.hr-baris-final  { background: var(--hr-hijau-bg); }
.hr-baris-warning { background: #FEF2F2; } /* alpa tinggi */

/* Badge status rekap */
.hr-badge-draft    { /* abu */ }
.hr-badge-final    { /* hijau */ }
.hr-badge-realtime { /* biru */ }

/* Progress bar di modal final */
.hr-progress-modal {
  height: 8px;
  border-radius: 4px;
  background: var(--hr-hijau-bg);
  overflow: hidden;
}
.hr-progress-modal-fill {
  height: 100%;
  background: var(--hr-hijau-utama);
  transition: width 0.3s ease;
}

/* Toast notifikasi */
.hr-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 14px;
  z-index: 9999;
  animation: slideInUp 0.3s ease;
}
.hr-toast.sukses  { background: var(--hr-hijau-utama); color: white; }
.hr-toast.error   { background: #DC2626; color: white; }
.hr-toast.warning { background: #D97706; color: white; }

@keyframes slideInUp {
  from { transform: translateY(20px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}
```

---

## Catatan Tambahan
- Tombol **Unduh Excel** harus tetap bisa diklik bahkan saat ada peringatan dokumen (download tidak diblokir, hanya diberi warning)
- Tombol **Tetapkan Semua sebagai Final** hanya muncul dan aktif jika ada rekap berstatus `draft` di tabel
- Saat proses generate atau tetapkan final sedang berjalan, disable semua tombol aksi untuk mencegah double-submit
- Modal konfirmasi generate harus menampilkan: periode yang dipilih, jumlah karyawan yang akan di-generate, dan warning jika ada filter (rekap tidak mencakup semua karyawan)
- Pastikan route `rekap` sudah didaftarkan di `routes/web.php` dalam group `role:hr`
