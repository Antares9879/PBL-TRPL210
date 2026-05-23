# Prompt 3 — Halaman Rekap Absensi HR (F15)

## Konteks Proyek

Kamu adalah Laravel 12 Fullstack Developer yang bekerja pada proyek **E-Outsourcing PT Ecogreen Oleochemicals Batam Plant** (PBL-TRPL210). Baca dan pahami file-file berikut sebelum mulai:

- `STRUKTUR-FOLDER.md` — panduan wajib struktur folder, konvensi penamaan, dan arsitektur proyek
- `resources/views/layouts/app.blade.php` — layout utama yang sudah ada
- `resources/views/hr/dashboard.blade.php` — halaman HR yang sudah dibuat (ambil pola sidebar-nya)
- `resources/css/hr.css` — CSS HR yang sudah ada (jangan buat ulang, hanya tambahkan class baru)
- `resources/views/hr/dokumen.blade.php` — referensi pola halaman A dua halaman HR
- `resources/views/hr/dokumen-detail.blade.php` — referensi pola halaman B dua halaman HR
- `resources/js/hr/dokumen.js` — referensi pola JS halaman A
- `resources/js/hr/dokumen-detail.js` — referensi pola JS halaman B
- `app/Http/Controllers/Api/HR/RekapApiController.php` — controller API yang akan di-consume
- `app/Services/RekapService.php` — referensi logika bisnis rekap

Ikuti **semua** aturan di `STRUKTUR-FOLDER.md` tanpa pengecualian.

---

## Tugas

Buat **dua halaman** Rekap Absensi HR (F15):

### Halaman A — Daftar Per Bulan
1. `resources/views/hr/rekap.blade.php`
2. `resources/js/hr/rekap.js`

### Halaman B — Detail Per Bulan (Daftar Rekap Per Karyawan)
1. `resources/views/hr/rekap-detail.blade.php`
2. `resources/js/hr/rekap-detail.js`

CSS tambahan dimasukkan ke `resources/css/hr.css` yang sudah ada.

Tambahkan dua route web baru di `routes/web.php` dalam group `role:hr`:
```php
Route::get('rekap', [HR\PageController::class, 'rekap'])->name('rekap');
Route::get('rekap/detail', [HR\PageController::class, 'rekapDetail'])->name('rekap.detail');
```

---

## Spesifikasi Halaman A — Daftar Per Bulan (`hr/rekap.blade.php`)

### Konsep Halaman
HR melihat 12 kartu bulan dalam satu tahun. Setiap kartu menampilkan status progresif rekap bulan tersebut dan tombol aksi yang relevan sesuai status. HR bisa generate rekap langsung dari kartu tanpa perlu filter tambahan.

### Struktur Layout
```
+--------------------------------------------------+
| SIDEBAR (sama dengan halaman HR lain)            |
+--------+-----------------------------------------+
| SIDEBAR | KONTEN UTAMA                            |
|         | Header: "Rekap Absensi Bulanan"         |
|         +-----------------------------------------+
|         | FILTER: dropdown tahun (default tahun   |
|         | berjalan), tombol Terapkan              |
|         +-----------------------------------------+
|         | GRID KARTU 12 BULAN                     |
|         | [Jan] [Feb] [Mar] [Apr]                 |
|         | [Mei] [Jun] [Jul] [Ags]                 |
|         | [Sep] [Okt] [Nov] [Des]                 |
+--------------------------------------------------+
```

### Sistem Status Progresif Kartu Bulan
Status bulan ditentukan berdasarkan kondisi paling "belum selesai" dalam hierarki berikut:

```
Belum Generate → Ada Draft → Semua Final
    (abu)           (biru)      (hijau)
```

**Logika penentuan status:**
- Jika masih ada satu karyawan yang belum digenerate rekap-nya → status bulan = **"Belum Generate"** (badge abu)
- Jika semua karyawan sudah digenerate tapi belum semua berstatus Final → status bulan = **"Ada Draft"** (badge biru)
- Jika semua karyawan sudah Final → status bulan = **"Selesai"** (badge hijau)

**Ringkasan angka** selalu ditampilkan di bawah badge status sebagai informasi sekunder:
```
[Badge: Ada Draft]
12 Final · 3 Draft · 5 Belum Generate
```

### Kartu Per Bulan
Setiap kartu menampilkan:
- **Nama bulan & tahun** (contoh: "Januari 2025") sebagai judul kartu
- **Badge status progresif** (Belum Generate / Ada Draft / Selesai)
- **Ringkasan angka** per kondisi: `{N} Final · {M} Draft · {K} Belum Generate`
- **Tombol aksi** yang muncul sesuai status bulan:

| Status Bulan   | Tombol yang Muncul |
|---|---|
| Belum Generate | `Generate Rekap` (hijau) + `Lihat Detail` |
| Ada Draft      | `Unduh Excel` + `Tetapkan Semua Final` + `Lihat Detail` |
| Selesai        | `Unduh Excel` + `Lihat Detail` |

- Bulan yang belum terjadi (bulan > bulan saat ini pada tahun yang sama) → kartu ditampilkan dengan opacity lebih rendah dan semua tombol aksi di-disable

### Alur Aksi "Generate Rekap"
1. HR klik "Generate Rekap" pada kartu bulan
2. Tampilkan **Pop-up Konfirmasi Generate**:
   ```
   ╔══════════════════════════════════════╗
   ║ 📊 Konfirmasi Generate Rekap         ║
   ║                                      ║
   ║ Generate rekap absensi untuk:        ║
   ║ Periode : {Bulan} {Tahun}            ║
   ║ Karyawan: Semua karyawan aktif       ║
   ║                                      ║
   ║ Rekap yang sudah ada (Draft) akan    ║
   ║ diperbarui. Rekap berstatus Final    ║
   ║ tidak akan diubah.                   ║
   ║                                      ║
   ║      [Batal]  [Ya, Generate]         ║
   ╚══════════════════════════════════════╝
   ```
3. HR klik "Ya, Generate" → `POST /api/hr/rekap/generate` dengan body `{ bulan, tahun }`
4. Tampilkan loading spinner di dalam kartu selama proses berlangsung
5. Setelah selesai → update kartu dengan status dan ringkasan terbaru tanpa reload halaman
6. Tampilkan toast sukses: "Rekap {Bulan} {Tahun} berhasil digenerate untuk {N} karyawan"

### Alur Aksi "Tetapkan Semua Final"
1. HR klik "Tetapkan Semua Final" pada kartu bulan
2. Sistem fetch `GET /api/hr/rekap/cek-dokumen?bulan=X&tahun=Y`
3. **Jika masih ada dokumen tidak lengkap** → tampilkan **Pop-up Peringatan** (tidak memblokir, hanya peringatan):
   ```
   ╔══════════════════════════════════════╗
   ║ ⚠️  Peringatan Dokumen Belum Lengkap ║
   ║                                      ║
   ║ Terdapat {N} pengajuan izin dengan   ║
   ║ dokumen belum lengkap/terverifikasi. ║
   ║                                      ║
   ║ • [Nama Karyawan] — [Jenis Izin]     ║
   ║   Status: [status_dokumen]           ║
   ║                                      ║
   ║ Rekap tetap dapat ditetapkan Final,  ║
   ║ namun data izin mungkin belum akurat.║
   ║                                      ║
   ║ [Batal] [Verifikasi Dulu] [Tetap Lanjutkan] ║
   ╚══════════════════════════════════════╝
   ```
   - "Verifikasi Dulu" → navigasi ke `/hr/dokumen/detail?bulan=X&tahun=Y`
   - "Tetap Lanjutkan" → lanjut ke langkah 4
4. **Jika semua dokumen lengkap** atau HR pilih "Tetap Lanjutkan" → tampilkan **Pop-up Konfirmasi Final**:
   ```
   ╔══════════════════════════════════════╗
   ║ ✅ Konfirmasi Tetapkan Final         ║
   ║                                      ║
   ║ Tetapkan semua rekap Draft menjadi   ║
   ║ Final untuk periode {Bulan} {Tahun}. ║
   ║                                      ║
   ║ Jumlah rekap Draft : {N}             ║
   ║ Rekap Final tidak akan diubah.       ║
   ║                                      ║
   ║ Aksi ini tidak dapat dibatalkan.     ║
   ║                                      ║
   ║      [Batal]  [Ya, Tetapkan Final]   ║
   ╚══════════════════════════════════════╝
   ```
5. HR klik "Ya, Tetapkan Final" → loop `POST /api/hr/rekap/{id}/final` untuk semua rekap Draft
6. Tampilkan progress bar di dalam kartu selama proses berlangsung
7. Setelah selesai → update kartu, tampilkan toast sukses

### Elemen HTML Shell yang Wajib Ada
```html
<!-- Filter tahun -->
<select id="filter-tahun-rekap"></select>
<button id="btn-terapkan-filter-rekap">Terapkan</button>

<!-- Grid 12 kartu bulan -->
<div id="grid-kartu-rekap">
  <!-- Diisi JS dengan 12 kartu, skeleton loading saat load -->
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

<!-- Modal peringatan dokumen belum lengkap -->
<div id="modal-peringatan-rekap" class="hr-modal" style="display:none;">
  <div class="hr-modal-content">
    <div id="modal-peringatan-rekap-body"></div>
    <div class="hr-modal-footer">
      <button id="btn-batal-peringatan-rekap">Batal</button>
      <button id="btn-verifikasi-dulu">Verifikasi Dulu</button>
      <button id="btn-lanjutkan-final" class="hr-btn-warning">Tetap Lanjutkan</button>
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
<div id="hr-toast-rekap" class="hr-toast" style="display:none;"></div>
```

---

## Spesifikasi Halaman B — Detail Per Bulan (`hr/rekap-detail.blade.php`)

### Konsep Halaman
Menampilkan daftar rekap per karyawan untuk satu periode bulan. HR dapat filter, unduh Excel, tetapkan Final per baris maupun bulk via checkbox, dan melihat detail rekap setiap karyawan.

### Cara Buka Halaman
URL: `/hr/rekap/detail?bulan=X&tahun=Y`
JS membaca `bulan` dan `tahun` dari `URLSearchParams` saat halaman load.

### Struktur Layout
```
+--------------------------------------------------+
| SIDEBAR (sama dengan halaman HR lain)            |
+--------+-----------------------------------------+
| SIDEBAR | KONTEN UTAMA                            |
|         | ← Kembali ke Daftar Rekap               |
|         | Header: "Detail Rekap — Jan 2025"       |
|         +-----------------------------------------+
|         | PANEL AKSI ATAS                         |
|         | [Unduh Excel] [Generate Ulang]          |
|         | [Tetapkan Semua Final (bulk)]           |
|         +-----------------------------------------+
|         | PANEL FILTER                            |
|         | [Departemen] [Perusahaan]               |
|         | [Status Rekap] [Search nama karyawan]   |
|         | [Terapkan Filter] [Reset]               |
|         +-----------------------------------------+
|         | TABS CEPAT STATUS:                      |
|         | [Semua] [Belum Generate] [Draft] [Final]|
|         +-----------------------------------------+
|         | STAT CARDS AGREGAT (4 kartu)            |
|         +-----------------------------------------+
|         | TABEL REKAP PER KARYAWAN (paginasi)     |
|         +-----------------------------------------+
|         | PAGINASI                                |
|         +-----------------------------------------+
|         | BULK ACTION BAR (sticky bottom)         |
+--------------------------------------------------+
```

### Panel Aksi Atas
- **Unduh Excel** → `GET /api/hr/rekap/unduh?bulan=&tahun=&id_departemen=&id_perusahaan=` — trigger browser download langsung via `window.location.href`
- **Generate Ulang** → generate rekap untuk semua karyawan aktif di bulan ini (sama dengan tombol di halaman A), tampilkan konfirmasi yang sama. Catatan: generate selalu untuk semua karyawan aktif, tidak dipengaruhi filter yang aktif
- **Tetapkan Semua Final** → tetapkan semua rekap berstatus `draft` di bulan ini (sesuai filter aktif), alur sama dengan halaman A termasuk cek dokumen dan pop-up peringatan/konfirmasi

### Panel Filter
- **Departemen**: dropdown diisi dari `GET /api/hr/dashboard/filter-options`
- **Perusahaan**: dropdown diisi dari `GET /api/hr/dashboard/filter-options`
- **Status Rekap**: dropdown (Semua | Belum Generate | Draft | Final)
- **Search**: input teks untuk cari nama atau nomor karyawan
- **Tombol Terapkan Filter** dan **Reset**

### Tabs Cepat Status
Shortcut filter status rekap. Klik tab langsung me-refresh tabel tanpa klik Terapkan.
```html
<div id="tabs-status-rekap">
  <button class="hr-tab aktif" data-status="">Semua</button>
  <button class="hr-tab" data-status="belum_generate">Belum Generate</button>
  <button class="hr-tab" data-status="draft">Draft</button>
  <button class="hr-tab" data-status="final">Final</button>
</div>
```

### Stat Cards Agregat
Tampilkan 4 kartu ringkasan yang update otomatis mengikuti filter aktif:
- **Total Karyawan** dalam tampilan saat ini
- **Total Menit Lembur Resmi** (sum semua karyawan yang tampil)
- **Total Hari Hadir** (sum)
- **Total Hari Alpa** (sum)

### Tabel Rekap Per Karyawan
Kolom:
| ☐ | No | Nama Karyawan | No. Karyawan | Departemen | Perusahaan | Hari Kerja | Hari Hadir | Hari Izin | Hari Alpa | Menit Normal | Menit Lembur | Menit Telat | Status | Digenerate Pada | Aksi |

**Keterangan kolom:**
- Kolom **☐** (checkbox): hanya baris berstatus `draft` yang bisa dicentang — `final` dan `belum_generate` di-disable
- Kolom **Status**: badge dengan tiga kondisi visual yang jelas:
  - `Belum Generate` → badge abu dengan ikon jam (⏱)
  - `Draft` → badge biru dengan ikon pensil (✏)
  - `Final` → badge hijau dengan ikon centang (✓)
- Kolom **Digenerate Pada**: tanggal & waktu rekap terakhir digenerate, atau "—" jika belum
- Kolom **Aksi**: tombol yang muncul sesuai status baris:
  - `Belum Generate` → tombol `Generate` (hijau kecil)
  - `Draft` → tombol `Lihat Detail` + tombol `Tetapkan Final` (biru kecil)
  - `Final` → tombol `Lihat Detail` saja (rekap sudah dikunci)
- Baris `Final` → background hijau sangat muda
- Baris `Draft` → background biru sangat muda
- Baris `Belum Generate` → background normal (putih)
- Baris dengan `total_hari_alpa > 3` → tambahkan ikon ⚠️ di samping angka pada kolom Hari Alpa sebagai perhatian HR

### Modal Detail Rekap Karyawan
Buka saat HR klik "Lihat Detail" pada baris tabel. Menampilkan:

**Bagian Info Karyawan:**
```
Nama Karyawan  : [nama]
No. Karyawan   : [nomor]
Departemen     : [nama]
Perusahaan     : [nama]
Posisi         : [posisi]
Periode        : [Bulan] [Tahun]
Status Rekap   : [badge]
Digenerate Pada: [waktu] oleh [nama HR]
```

**Bagian Data Rekap (dua kolom):**
```
Kehadiran                    Waktu Kerja
──────────────────────       ──────────────────────
Hari Kerja  : [N] hari       Menit Normal  : [N] mnt
Hari Hadir  : [N] hari       Menit Lembur  : [N] mnt
Hari Izin   : [N] hari       Menit Telat   : [N] mnt
Hari Alpa   : [N] hari       Menit Plg Cpt : [N] mnt
```

**Bagian Aksi di Modal (kondisional sesuai status):**
- Status `Draft` → tampilkan tombol `Tetapkan Final` dengan konfirmasi
- Status `Final` → tampilkan label "Rekap ini sudah dikunci sebagai Final"
- Status `Belum Generate` → tampilkan tombol `Generate Rekap Ini`

### Alur Aksi "Tetapkan Final" per Baris (dari tabel atau modal)
1. HR klik "Tetapkan Final" pada satu baris atau di dalam modal
2. Sistem fetch `GET /api/hr/rekap/cek-dokumen?bulan=X&tahun=Y` untuk periode tersebut
3. **Jika ada dokumen tidak lengkap** → tampilkan pop-up peringatan (sama seperti halaman A) dengan opsi Batal / Verifikasi Dulu / Tetap Lanjutkan
4. **Jika dokumen lengkap** atau HR pilih "Tetap Lanjutkan" → tampilkan konfirmasi singkat:
   ```
   Tetapkan rekap [Nama Karyawan] periode [Bulan Tahun] sebagai Final?
   Aksi ini tidak dapat dibatalkan.
   [Batal] [Ya, Final]
   ```
5. Setelah `POST /api/hr/rekap/{id}/final` berhasil → update hanya baris yang bersangkutan tanpa reload penuh halaman

### Alur Bulk Action via Checkbox
1. HR centang beberapa baris berstatus `draft`
2. Muncul **action bar sticky** di bagian bawah halaman:
   ```
   {N} rekap dipilih  [Tetapkan Final ({N})]  [Batalkan Pilihan]
   ```
3. HR klik "Tetapkan Final" → cek dokumen → pop-up peringatan (jika perlu) → konfirmasi → loop final
4. Progress ditampilkan di modal progress bar selama proses berlangsung
5. Setelah selesai → bersihkan semua checkbox, sembunyikan action bar, refresh tabel

### Elemen HTML Shell yang Wajib Ada
```html
<!-- Info periode & navigasi -->
<div id="info-periode-rekap-detail"></div>
<a href="/hr/rekap" id="btn-kembali-rekap">← Kembali</a>

<!-- Panel aksi atas -->
<button id="btn-unduh-excel-detail">Unduh Excel</button>
<button id="btn-generate-ulang-detail">Generate Ulang</button>
<button id="btn-final-semua-detail">Tetapkan Semua Final</button>

<!-- Filter panel -->
<select id="filter-departemen-detail"></select>
<select id="filter-perusahaan-detail"></select>
<select id="filter-status-rekap-detail">
  <option value="">Semua Status</option>
  <option value="belum_generate">Belum Generate</option>
  <option value="draft">Draft</option>
  <option value="final">Final</option>
</select>
<input type="text" id="filter-search-rekap" placeholder="Cari nama atau nomor karyawan...">
<button id="btn-terapkan-filter-detail">Terapkan Filter</button>
<button id="btn-reset-filter-detail">Reset</button>

<!-- Tabs cepat -->
<div id="tabs-status-rekap"></div>

<!-- Stat cards agregat -->
<div id="panel-agregat-detail">
  <div id="card-total-karyawan-detail"></div>
  <div id="card-total-lembur-detail"></div>
  <div id="card-total-hadir-detail"></div>
  <div id="card-total-alpa-detail"></div>
</div>

<!-- Tabel + paginasi -->
<div id="tabel-rekap-detail"></div>
<div id="paginasi-rekap-detail"></div>

<!-- Bulk action bar sticky -->
<div id="bulk-action-bar" class="hr-bulk-action-bar" style="display:none;">
  <span id="bulk-count-label"></span>
  <button id="btn-bulk-final">
    Tetapkan Final (<span id="bulk-count">0</span>)
  </button>
  <button id="btn-batal-bulk">Batalkan Pilihan</button>
</div>

<!-- Modal detail rekap karyawan -->
<div id="modal-detail-rekap" class="hr-modal" style="display:none;">
  <div class="hr-modal-content hr-modal-lg">
    <div class="hr-modal-header">
      <h3 id="modal-detail-rekap-title">Detail Rekap</h3>
      <button id="btn-tutup-modal-detail-rekap">×</button>
    </div>
    <div id="modal-detail-rekap-body"></div>
  </div>
</div>

<!-- Modal peringatan dokumen (halaman B) -->
<div id="modal-peringatan-rekap-detail" class="hr-modal" style="display:none;">
  <div class="hr-modal-content">
    <div id="modal-peringatan-rekap-detail-body"></div>
    <div class="hr-modal-footer">
      <button id="btn-batal-peringatan-detail">Batal</button>
      <button id="btn-verifikasi-dulu-detail">Verifikasi Dulu</button>
      <button id="btn-lanjutkan-final-detail" class="hr-btn-warning">Tetap Lanjutkan</button>
    </div>
  </div>
</div>

<!-- Modal konfirmasi final per baris -->
<div id="modal-konfirmasi-final-detail" class="hr-modal" style="display:none;">
  <div class="hr-modal-content">
    <div id="modal-konfirmasi-final-detail-body"></div>
    <div class="hr-modal-footer">
      <button id="btn-batal-final-detail">Batal</button>
      <button id="btn-submit-final-detail" class="hr-btn-primary">Ya, Final</button>
    </div>
  </div>
</div>

<!-- Modal progress bulk final -->
<div id="modal-progress-bulk" class="hr-modal" style="display:none;">
  <div class="hr-modal-content">
    <p id="modal-progress-label">Menetapkan Final...</p>
    <div class="hr-progress-modal">
      <div class="hr-progress-modal-fill" id="progress-bulk-fill" style="width:0%"></div>
    </div>
    <p id="modal-progress-count"></p>
  </div>
</div>

<!-- Toast notifikasi -->
<div id="hr-toast-rekap-detail" class="hr-toast" style="display:none;"></div>
```

---

## Spesifikasi JS Halaman A (`hr/rekap.js`)

### Endpoint API yang Digunakan
```
GET  /api/hr/rekap?bulan=&tahun=&status_rekap=     — ambil data rekap per karyawan untuk satu bulan
GET  /api/hr/rekap/cek-dokumen?bulan=&tahun=       — cek kelengkapan dokumen sebelum final
POST /api/hr/rekap/generate                         — generate rekap (body: { bulan, tahun })
POST /api/hr/rekap/{id}/final                      — tetapkan satu rekap sebagai final
```

### Alur Kerja JS Halaman A
1. Load → inisialisasi dropdown tahun (5 tahun terakhir, default tahun ini)
2. `loadSemuaBulan(tahun)` → untuk setiap bulan (1–12) fetch data rekap lalu render 12 kartu
3. Setiap kartu dihitung statusnya dari data response menggunakan `hitungStatusBulan()`
4. Render badge status dan ringkasan angka pada kartu
5. Disable semua tombol aksi untuk bulan yang belum terjadi

### Fungsi Utama
```javascript
function hitungStatusBulan(dataRekap, totalKaryawanAktif) {
  const jumlahFinal    = dataRekap.filter(r => r.status_rekap === 'final').length;
  const jumlahDraft    = dataRekap.filter(r => r.status_rekap === 'draft').length;
  const jumlahGenerate = jumlahFinal + jumlahDraft;
  const belumGenerate  = totalKaryawanAktif - jumlahGenerate;

  if (belumGenerate > 0)              return { status: 'belum_generate', label: 'Belum Generate', warna: 'abu' };
  if (jumlahFinal < jumlahGenerate)   return { status: 'ada_draft',      label: 'Ada Draft',      warna: 'biru' };
  return                                     { status: 'selesai',         label: 'Selesai',         warna: 'hijau' };
}

async function generateRekap(bulan, tahun, idKartu) {
  // 1. Tampilkan modal konfirmasi generate dengan detail periode
  // 2. Setelah konfirmasi: POST /api/hr/rekap/generate
  // 3. Tampilkan loading overlay di kartu yang bersangkutan (idKartu)
  // 4. Setelah selesai: reload data kartu bulan itu saja (bukan reload semua)
  // 5. Tampilkan toast sukses
}

async function tetapkanSemuaFinal(bulan, tahun, idKartu) {
  // 1. Fetch cek-dokumen
  // 2. Jika ada masalah → tampilkan modal peringatan 3 tombol
  // 3. Jika lanjut → ambil semua id_rekap berstatus draft untuk bulan itu
  //    via GET /api/hr/rekap?bulan=X&tahun=Y&status_rekap=draft
  // 4. Tampilkan modal konfirmasi final dengan jumlah rekap
  // 5. Loop POST /api/hr/rekap/{id}/final dengan progress bar di dalam kartu
  // 6. Setelah selesai: reload kartu, tampilkan toast
}
```

---

## Spesifikasi JS Halaman B (`hr/rekap-detail.js`)

### Endpoint API yang Digunakan
```
GET  /api/hr/rekap?bulan=&tahun=&id_departemen=&id_perusahaan=&status_rekap=&page=
GET  /api/hr/dashboard/filter-options
GET  /api/hr/rekap/cek-dokumen?bulan=&tahun=
GET  /api/hr/rekap/unduh?bulan=&tahun=&id_departemen=&id_perusahaan=
POST /api/hr/rekap/generate
POST /api/hr/rekap/{id}/final
```

### Alur Kerja JS Halaman B
1. Load → baca `bulan` & `tahun` dari `URLSearchParams`
2. `loadFilterOptions()` → isi dropdown departemen & perusahaan
3. `loadRekapDetail(params)` → render tabel + paginasi + stat cards agregat
4. Event listener filter panel, tabs, checkbox → re-load atau update UI
5. Klik "Lihat Detail" → render modal detail dengan data dari baris tabel
6. Klik "Tetapkan Final" per baris atau di modal → alur cek dokumen → konfirmasi → final
7. Klik "Unduh Excel" → `window.location.href` ke URL unduh dengan parameter filter aktif

### Manajemen Checkbox & Bulk Action
```javascript
let selectedIds = new Set();

function toggleCheckbox(idRekap, checked) {
  if (checked) selectedIds.add(idRekap);
  else selectedIds.delete(idRekap);
  updateBulkActionBar();
}

function updateBulkActionBar() {
  const count = selectedIds.size;
  document.getElementById('bulk-action-bar').style.display = count > 0 ? 'flex' : 'none';
  document.getElementById('bulk-count').textContent = count;
}

function togglePilihSemua(checked) {
  // Pilih/deselect semua baris berstatus 'draft' yang tampil di halaman ini
  // Update selectedIds dan semua checkbox di tabel
}
```

### Hitung Stat Cards Agregat
```javascript
function hitungAgregat(dataRekap) {
  // Hitung dari data yang saat ini tampil di tabel (bukan semua data DB)
  return {
    total_karyawan    : dataRekap.length,
    total_menit_lembur: dataRekap.reduce((sum, r) => sum + (r.total_menit_lembur || 0), 0),
    total_hari_hadir  : dataRekap.reduce((sum, r) => sum + (r.total_hari_hadir  || 0), 0),
    total_hari_alpa   : dataRekap.reduce((sum, r) => sum + (r.total_hari_alpa   || 0), 0),
  };
}
```

### Bulk Final dengan Progress Bar
```javascript
async function bulkFinal(listIdRekap) {
  // Tampilkan modal progress
  let berhasil = 0, gagal = 0;
  for (let i = 0; i < listIdRekap.length; i++) {
    try {
      const res = await fetch(`/api/hr/rekap/${listIdRekap[i]}/final`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' }
      });
      if ((await res.json()).status) berhasil++;
      else gagal++;
    } catch { gagal++; }
    const pct = Math.round(((i + 1) / listIdRekap.length) * 100);
    document.getElementById('progress-bulk-fill').style.width = pct + '%';
    document.getElementById('modal-progress-count').textContent =
      `${i + 1} dari ${listIdRekap.length} diproses...`;
  }
  // Setelah selesai: tutup modal, bersihkan selectedIds,
  // tampilkan toast "{berhasil} berhasil, {gagal} gagal", reload tabel
}
```

---

## CSS Tambahan untuk `hr.css`

Tambahkan class baru berikut (jangan timpa yang sudah ada):

```css
/* Kartu bulan rekap */
.hr-kartu-bulan-rekap {
  position: relative; /* untuk loading overlay */
  min-height: 200px;
  /* border, padding, border-radius mengikuti pola .hr-kartu-bulan dari prompt 2 */
}
.hr-kartu-bulan-rekap.belum-terjadi {
  opacity: 0.45;
  pointer-events: none;
}

/* Loading overlay di dalam kartu */
.hr-kartu-loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.82);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: inherit;
  z-index: 10;
}

/* Badge status progresif bulan */
.hr-badge-belum-generate { background: #E5E7EB; color: #374151; }
.hr-badge-ada-draft      { background: #DBEAFE; color: #1E3A5F; }
.hr-badge-selesai        { background: #DCFCE7; color: #14532D; }

/* Ringkasan angka di kartu bulan */
.hr-ringkasan-angka {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 6px;
  line-height: 1.6;
}

/* Badge status rekap di tabel */
.hr-badge-belum-generate-tabel { background: #E5E7EB; color: #374151; }
.hr-badge-draft-tabel          { background: #DBEAFE; color: #1E3A5F; }
.hr-badge-final-tabel          { background: #DCFCE7; color: #14532D; }

/* Baris tabel per status */
.hr-baris-final { background: #F0FDF4; }
.hr-baris-draft { background: #EFF6FF; }

/* Bulk action bar sticky */
.hr-bulk-action-bar {
  position: sticky;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--hr-hijau-tua);
  color: white;
  padding: 12px 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 100;
  border-top: 2px solid var(--hr-hijau-muda);
}

/* Tombol warning */
.hr-btn-warning {
  background: #D97706;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
}
.hr-btn-warning:hover { background: #B45309; }

/* Progress bar modal bulk */
.hr-progress-modal {
  height: 8px;
  border-radius: 4px;
  background: var(--hr-hijau-bg);
  overflow: hidden;
  margin: 12px 0;
}
.hr-progress-modal-fill {
  height: 100%;
  background: var(--hr-hijau-utama);
  transition: width 0.3s ease;
}

/* Dua kolom data rekap di modal detail */
.hr-rekap-detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-top: 16px;
}
.hr-rekap-detail-section {
  background: var(--hr-hijau-bg);
  border-radius: 8px;
  padding: 14px;
}
.hr-rekap-detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  padding: 4px 0;
  border-bottom: 0.5px solid var(--hr-hijau-border);
}
.hr-rekap-detail-row:last-child { border-bottom: none; }
```

---

## Catatan Tambahan

- **Urutan pengerjaan yang disarankan**: selesaikan Halaman A dulu sebelum Halaman B karena pola modal peringatan, konfirmasi, dan generate yang sama akan digunakan ulang di Halaman B
- Checkbox di tabel hanya bisa dicentang untuk baris berstatus `draft` — `final` dan `belum_generate` di-disable
- Saat proses generate atau final sedang berjalan, disable semua tombol aksi untuk mencegah double-submit
- Tombol "Generate Ulang" di Halaman B menampilkan konfirmasi yang sama dengan Halaman A, dengan tambahan keterangan bahwa filter aktif tidak mempengaruhi generate — selalu untuk semua karyawan aktif
- Modal detail rekap menampilkan tombol aksi yang berbeda sesuai status rekap — pastikan tombol dirender ulang setiap kali modal dibuka
- Saat bulk final selesai, bersihkan `selectedIds` dan sembunyikan action bar sticky
- `hr-toast` dan class modal dasar sudah didefinisikan di Prompt 2 — gunakan kembali, jangan deklarasi ulang di CSS
- Pastikan kedua route sudah didaftarkan di `routes/web.php` dalam group `role:hr`