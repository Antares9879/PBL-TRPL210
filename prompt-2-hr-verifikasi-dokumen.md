# Prompt 2 — Halaman Verifikasi Dokumen HR (F14)

## Konteks Proyek

Kamu adalah Laravel 12 Fullstack Developer yang bekerja pada proyek **E-Outsourcing PT Ecogreen Oleochemicals Batam Plant** (PBL-TRPL210). Baca dan pahami file-file berikut sebelum mulai:

- `STRUKTUR-FOLDER.md` — panduan wajib struktur folder, konvensi penamaan, dan arsitektur proyek
- `resources/views/layouts/app.blade.php` — layout utama yang sudah ada
- `resources/views/hr/dashboard.blade.php` — halaman HR yang sudah dibuat (ambil pola sidebar-nya)
- `resources/css/hr.css` — CSS HR yang sudah ada (jangan buat ulang, hanya tambahkan class baru)
- `resources/js/admin-outsource/kelola-izin.js` — referensi pola JS untuk halaman izin
- `app/Http/Controllers/Api/HR/DokumenApiController.php` — controller API yang akan di-consume
- `app/Http/Controllers/HR/PageController.php` — tambahkan method untuk halaman ini

Ikuti **semua** aturan di `STRUKTUR-FOLDER.md` tanpa pengecualian.

---

## Tugas

Buat **dua halaman** Verifikasi Dokumen HR (F14):

### Halaman A — Daftar Per Bulan
1. `resources/views/hr/dokumen.blade.php`
2. `resources/js/hr/dokumen.js`

### Halaman B — Detail Per Bulan (Daftar Pengajuan)
1. `resources/views/hr/dokumen-detail.blade.php`
2. `resources/js/hr/dokumen-detail.js`

CSS tambahan dimasukkan ke `resources/css/hr.css` yang sudah ada.

Tambahkan dua route web baru di `routes/web.php`:
```php
Route::get('dokumen', [HR\PageController::class, 'dokumen'])->name('dokumen');
Route::get('dokumen/detail', [HR\PageController::class, 'dokumenDetail'])->name('dokumen.detail');
```

---

## Spesifikasi Halaman A — Daftar Per Bulan (`hr/dokumen.blade.php`)

### Konsep Halaman
HR melihat daftar bulan sebagai kartu/baris. Setiap item mewakili satu periode bulan dan menampilkan ringkasan status kelengkapan dokumen. HR dapat langsung melakukan aksi verifikasi massal dari halaman ini.

### Struktur Layout
```
+--------------------------------------------------+
| SIDEBAR (sama dengan dashboard)                  |
+--------+-----------------------------------------+
| SIDEBAR | KONTEN UTAMA                            |
|         | Header: "Verifikasi Dokumen Izin"       |
|         +-----------------------------------------+
|         | FILTER: dropdown tahun (default tahun   |
|         | berjalan), tombol Terapkan              |
|         +-----------------------------------------+
|         | LIST KARTU PER BULAN                    |
|         | [Januari 2025] [badge ringkasan]        |
|         | [Februari 2025] [badge ringkasan]       |
|         | ... dst                                 |
+--------------------------------------------------+
```

### Kartu Per Bulan
Setiap kartu bulan menampilkan:
- **Nama bulan & tahun** (contoh: "Januari 2025")
- **Badge ringkasan dokumen**: `{n} Lengkap` (hijau) | `{n} Tidak Lengkap` (merah) | `{n} Belum Diverifikasi` (abu) | `{n} Belum Upload` (kuning)
- **Progress bar**: persentase dokumen yang sudah lengkap dari total pengajuan izin
- **Tombol "Lihat Detail"** → navigasi ke Halaman B dengan query string `?bulan=X&tahun=Y`
- **Tombol "Setujui Semua"** → trigger aksi verifikasi massal (lihat alur di bawah)
- **Status disabled** pada tombol "Setujui Semua" jika masih ada dokumen belum lengkap

### Alur Aksi "Setujui Semua"
1. HR klik tombol "Setujui Semua" pada kartu bulan tertentu
2. **Sistem cek status dokumen** via `GET /api/hr/rekap/cek-dokumen?bulan=X&tahun=Y`
3. **Jika ada dokumen belum lengkap** → tampilkan **Pop-up Peringatan**:
   ```
   ╔══════════════════════════════════════╗
   ║ ⚠️  Ada Dokumen Belum Lengkap        ║
   ║                                      ║
   ║ Terdapat {N} pengajuan izin dengan   ║
   ║ dokumen belum lengkap/terverifikasi: ║
   ║                                      ║
   ║ • [Nama Karyawan] — [Jenis Izin]     ║
   ║   Status: Belum Upload               ║
   ║ • [Nama Karyawan] — [Jenis Izin]     ║
   ║   Status: Tidak Lengkap              ║
   ║                                      ║
   ║ Semua dokumen harus lengkap sebelum  ║
   ║ bulan ini dapat disetujui.           ║
   ║                                      ║
   ║      [Tutup] [Lihat Detail]          ║
   ╚══════════════════════════════════════╝
   ```
   - Tombol "Lihat Detail" → navigasi ke Halaman B
   - Tidak ada tombol "Tetap Setujui" — aksi diblokir penuh
4. **Jika semua dokumen sudah lengkap** → tampilkan **Pop-up Konfirmasi**:
   ```
   ╔══════════════════════════════════════╗
   ║ ✅  Konfirmasi Persetujuan           ║
   ║                                      ║
   ║ Anda akan menyetujui semua dokumen   ║
   ║ izin untuk periode {Bulan} {Tahun}.  ║
   ║                                      ║
   ║ Total pengajuan: {N} pengajuan       ║
   ║ Semua dokumen: Lengkap ✓             ║
   ║                                      ║
   ║ Aksi ini tidak dapat dibatalkan.     ║
   ║                                      ║
   ║      [Batal]  [Ya, Setujui]          ║
   ╚══════════════════════════════════════╝
   ```
5. HR klik "Ya, Setujui" → kirim request generate rekap + tetapkan Final
6. Setelah berhasil → tampilkan toast sukses, update kartu bulan

### Elemen HTML Shell yang Wajib Ada
```html
<!-- Filter -->
<select id="filter-tahun-dokumen"></select>
<button id="btn-terapkan-filter-dokumen">Terapkan</button>

<!-- Container list bulan -->
<div id="list-bulan-dokumen">
  <!-- Diisi JS, skeleton loading saat load -->
</div>

<!-- Modal peringatan dokumen belum lengkap -->
<div id="modal-peringatan-dokumen" class="hr-modal" style="display:none;">
  <div class="hr-modal-content">
    <div id="modal-peringatan-body"></div>
    <div class="hr-modal-footer">
      <button id="btn-tutup-peringatan">Tutup</button>
      <button id="btn-lihat-detail-peringatan">Lihat Detail</button>
    </div>
  </div>
</div>

<!-- Modal konfirmasi setujui -->
<div id="modal-konfirmasi-setujui" class="hr-modal" style="display:none;">
  <div class="hr-modal-content">
    <div id="modal-konfirmasi-body"></div>
    <div class="hr-modal-footer">
      <button id="btn-batal-setujui">Batal</button>
      <button id="btn-ya-setujui" class="hr-btn-primary">Ya, Setujui</button>
    </div>
  </div>
</div>
```

---

## Spesifikasi Halaman B — Detail Per Bulan (`hr/dokumen-detail.blade.php`)

### Konsep Halaman
Halaman ini menampilkan daftar **semua pengajuan izin** dalam satu periode bulan yang dipilih. HR dapat melihat detail per pengajuan dan memverifikasi dokumen satu per satu.

### Cara Buka Halaman
URL: `/hr/dokumen/detail?bulan=X&tahun=Y`
JS membaca `bulan` dan `tahun` dari `URLSearchParams` saat halaman load.

### Struktur Layout
```
+--------------------------------------------------+
| SIDEBAR (sama dengan halaman lain)               |
+--------+-----------------------------------------+
| SIDEBAR | KONTEN UTAMA                            |
|         | ← Kembali ke Daftar Dokumen             |
|         | Header: "Detail Dokumen — Jan 2025"     |
|         +-----------------------------------------+
|         | PANEL FILTER                            |
|         | [Jenis Izin] [Status Dokumen]            |
|         | [Tanggal Dari] [Tanggal Sampai]          |
|         | [Perusahaan Outsource] [Departemen]      |
|         | [Search nama karyawan]                   |
|         | [Terapkan Filter]  [Reset]               |
|         +-----------------------------------------+
|         | TABS CEPAT (toggle filter status):      |
|         | [Semua] [Belum Diverifikasi] [Lengkap]  |
|         | [Tidak Lengkap] [Belum Upload]           |
|         +-----------------------------------------+
|         | TABEL PENGAJUAN IZIN (paginasi)         |
|         +-----------------------------------------+
|         | PAGINASI                                |
+--------------------------------------------------+
```

### Panel Filter
- **Jenis Izin**: dropdown (Sakit | Cuti | Keperluan Keluarga | Keperluan Lain | Semua)
- **Status Dokumen**: dropdown (Semua | Belum Upload | Sudah Upload | Lengkap | Tidak Lengkap)
- **Range Tanggal**: dua input `type="date"` (Dari — Sampai)
- **Perusahaan Outsource**: dropdown diisi dari `GET /api/hr/dashboard/filter-options`
- **Departemen**: dropdown diisi dari `GET /api/hr/dashboard/filter-options`
- **Search**: input teks untuk cari nama karyawan
- **Tombol Terapkan Filter** dan **Reset**

### Tabs Cepat Status
Tabs ini merupakan shortcut filter status dokumen. Klik tab langsung me-refresh tabel tanpa klik Terapkan.
```html
<div id="tabs-status-dokumen">
  <button class="hr-tab aktif" data-status="">Semua</button>
  <button class="hr-tab" data-status="sudah_upload">Belum Diverifikasi</button>
  <button class="hr-tab" data-status="lengkap">Lengkap</button>
  <button class="hr-tab" data-status="tidak_lengkap">Tidak Lengkap</button>
  <button class="hr-tab" data-status="belum_upload">Belum Upload</button>
</div>
```

### Tabel Pengajuan Izin
Kolom:
| No | Nama Karyawan | Departemen | Perusahaan | Jenis Izin | Tanggal Izin | Jumlah Hari | Jumlah Dokumen | Status Dokumen | Aksi |

- **Status Dokumen** menggunakan badge berwarna
- Kolom **Aksi** berisi dua tombol:
  - `Lihat Detail` → buka **Modal Detail Pengajuan**
  - `Tandai Lengkap` (hijau) atau `Tandai Tidak Lengkap` (merah) tergantung status saat ini

### Modal Detail Pengajuan Izin
Buka saat HR klik "Lihat Detail" pada baris tabel. Menampilkan:

**Bagian Info Pengajuan:**
```
Nama Karyawan  : [nama]
NIK/No Karyawan: [nomor]
Departemen     : [nama]
Perusahaan     : [nama]
Jenis Izin     : [jenis]
Tanggal Izin   : [tanggal mulai] — [tanggal selesai] ([N] hari)
Keterangan     : [teks keterangan]
Status Izin    : [badge]
Disetujui oleh : [nama admin] pada [tanggal]
```

**Bagian Dokumen:**
- Daftar file yang sudah diupload dalam format kartu kecil:
  - Nama file, ukuran (KB), tanggal upload
  - Tombol **Preview** → buka lightbox modal dalam halaman yang sama
  - Tombol **Buka di Tab Baru** → `window.open(url, '_blank')`
- Jika belum ada dokumen → tampilkan pesan "Belum ada dokumen yang diunggah"

**Lightbox Preview Dokumen:**
- Untuk PDF: tampilkan dalam `<iframe>` di dalam modal fullscreen
- Untuk gambar (JPG/PNG): tampilkan dalam `<img>` dengan max-height 80vh
- Tombol close (×) di pojok kanan atas
- Tombol "Buka di Tab Baru" di toolbar lightbox

**Bagian Aksi Verifikasi di Modal:**
```
Status Dokumen saat ini: [badge status]

[Input: Catatan Kekurangan — muncul hanya jika aksi = Tandai Tidak Lengkap]

[Tombol: Tandai Lengkap ✓] [Tombol: Tandai Tidak Lengkap ✗]
```

**Pop-up Konfirmasi sebelum aksi verifikasi:**
- "Tandai Lengkap" → konfirmasi singkat: "Tandai dokumen pengajuan ini sebagai Lengkap?"
- "Tandai Tidak Lengkap" → konfirmasi dengan input catatan wajib diisi

### Elemen HTML Shell yang Wajib Ada
```html
<!-- Info periode -->
<div id="info-periode-detail"></div>
<a href="/hr/dokumen" id="btn-kembali">← Kembali</a>

<!-- Filter panel -->
<select id="filter-jenis-izin"></select>
<select id="filter-status-dokumen"></select>
<input type="date" id="filter-tanggal-dari">
<input type="date" id="filter-tanggal-sampai">
<select id="filter-perusahaan"></select>
<select id="filter-departemen"></select>
<input type="text" id="filter-search" placeholder="Cari nama karyawan...">
<button id="btn-terapkan-filter-detail">Terapkan Filter</button>
<button id="btn-reset-filter">Reset</button>

<!-- Tabs cepat -->
<div id="tabs-status-dokumen">...</div>

<!-- Tabel + paginasi -->
<div id="tabel-pengajuan-izin"></div>
<div id="paginasi-pengajuan"></div>

<!-- Modal detail pengajuan -->
<div id="modal-detail-izin" class="hr-modal" style="display:none;">
  <div class="hr-modal-content hr-modal-lg">
    <div id="modal-detail-body"></div>
  </div>
</div>

<!-- Lightbox preview dokumen -->
<div id="lightbox-dokumen" class="hr-lightbox" style="display:none;">
  <div class="hr-lightbox-toolbar">
    <span id="lightbox-nama-file"></span>
    <button id="btn-lightbox-tab-baru">Buka di Tab Baru</button>
    <button id="btn-lightbox-close">×</button>
  </div>
  <div id="lightbox-content"></div>
</div>

<!-- Modal konfirmasi verifikasi -->
<div id="modal-konfirmasi-verifikasi" class="hr-modal" style="display:none;">
  <div class="hr-modal-content">
    <div id="modal-konfirmasi-verifikasi-body"></div>
    <textarea id="input-catatan-dokumen" placeholder="Tuliskan kekurangan dokumen..." style="display:none;"></textarea>
    <div class="hr-modal-footer">
      <button id="btn-batal-verifikasi">Batal</button>
      <button id="btn-submit-verifikasi" class="hr-btn-primary">Konfirmasi</button>
    </div>
  </div>
</div>
```

---

## Spesifikasi JS Halaman A (`hr/dokumen.js`)

### Endpoint API yang Digunakan
```
GET /api/hr/rekap/cek-dokumen?bulan=X&tahun=Y       — cek kelengkapan sebelum aksi
GET /api/hr/dokumen?bulan=X&tahun=Y&status_dokumen= — ambil ringkasan per bulan
```

### Alur Kerja
1. Load → inisialisasi dropdown tahun (5 tahun terakhir, default tahun ini)
2. `loadDaftarBulan(tahun)` → fetch daftar 12 bulan, render kartu per bulan
3. Setiap kartu bulan: hitung jumlah per status dokumen dari response API
4. Event tombol "Setujui Semua":
   - Fetch `cek-dokumen` → jika ada masalah → buka `#modal-peringatan-dokumen`
   - Jika bersih → buka `#modal-konfirmasi-setujui`
5. Event "Ya, Setujui" di modal → `POST /api/hr/rekap/generate` lalu `POST /api/hr/rekap/{id}/final`

### Render Kartu Bulan
```javascript
function renderKartuBulan(data) {
  // data: { bulan, tahun, label, jumlah_lengkap, jumlah_tidak_lengkap,
  //         jumlah_belum_verifikasi, jumlah_belum_upload, total }
  // Hitung persentase: (jumlah_lengkap / total) * 100
  // Render progress bar berdasarkan persentase
  // Disable tombol "Setujui Semua" jika jumlah_tidak_lengkap > 0 || jumlah_belum_upload > 0
}
```

---

## Spesifikasi JS Halaman B (`hr/dokumen-detail.js`)

### Endpoint API yang Digunakan
```
GET /api/hr/dokumen?bulan=&tahun=&status_dokumen=&id_departemen=&id_perusahaan=&search=&page=
GET /api/hr/dokumen/{id}
GET /api/hr/dashboard/filter-options
GET /api/hr/dokumen/{id}/stream/{docId}    — untuk preview dokumen
POST /api/hr/dokumen/{id}/verifikasi
```

### Alur Kerja
1. Load → baca `bulan` & `tahun` dari `URLSearchParams`
2. `loadFilterOptions()` → isi dropdown perusahaan & departemen
3. `loadPengajuanIzin(params)` → render tabel + paginasi
4. Event listener filter panel & tabs → re-load tabel
5. Klik "Lihat Detail" → fetch `GET /api/hr/dokumen/{id}` → render modal
6. Klik "Preview" pada dokumen → buka lightbox:
   - Fetch URL stream: `/api/hr/dokumen/{id_izin}/stream/{docId}`
   - PDF: render `<iframe src="blob:...">` atau direct URL
   - Gambar: render `<img src="...">`
7. Klik "Tandai Lengkap"/"Tandai Tidak Lengkap" → buka modal konfirmasi
8. Submit konfirmasi → `POST /api/hr/dokumen/{id}/verifikasi` → refresh baris tabel

### Fungsi Penting
```javascript
function bukaLightbox(idIzin, idDokumen, namaFile, tipeFile) {
  // Buka lightbox, tampilkan loading spinner
  // Fetch URL: /api/hr/dokumen/{idIzin}/stream/{idDokumen}
  // Jika PDF: render <iframe>
  // Jika gambar: render <img>
  // Set tombol "Buka di Tab Baru" dengan href yang sama
}

function submitVerifikasi(idIzin, aksi, catatan) {
  // POST ke /api/hr/dokumen/{idIzin}/verifikasi
  // Body: { aksi: 'tandai_lengkap'|'tandai_tidak_lengkap', catatan_dokumen: catatan }
  // Setelah sukses: tutup modal, refresh baris tabel tanpa reload penuh
}
```

---

## CSS Tambahan untuk `hr.css`

Tambahkan class baru berikut (jangan timpa yang sudah ada):

```css
/* Modal HR */
.hr-modal { /* overlay fullscreen */ }
.hr-modal-content { /* container putih dengan border-radius */ }
.hr-modal-lg { /* modal lebih lebar untuk detail izin */ }
.hr-modal-footer { /* flex row tombol */ }

/* Lightbox */
.hr-lightbox { /* overlay hitam 95% opacity, z-index tinggi */ }
.hr-lightbox-toolbar { /* bar atas dengan nama file dan tombol */ }

/* Tabs */
.hr-tab { /* tombol tab */ }
.hr-tab.aktif { /* tab aktif dengan warna hijau */ }

/* Kartu bulan */
.hr-kartu-bulan { /* card dengan border, shadow ringan */ }
.hr-progress-bar-container { /* track progress bar */ }
.hr-progress-bar-fill { /* fill hijau dengan transition */ }

/* Badges status dokumen */
.hr-badge-lengkap        { /* hijau */ }
.hr-badge-tidak-lengkap  { /* merah */ }
.hr-badge-belum-upload   { /* kuning/amber */ }
.hr-badge-sudah-upload   { /* biru — sudah upload tapi belum diverifikasi */ }

/* Tombol aksi */
.hr-btn-primary  { /* hijau solid */ }
.hr-btn-danger   { /* merah solid */ }
.hr-btn-outline  { /* border hijau, bg transparan */ }
```

---

## Catatan Tambahan
- Lightbox harus menutup saat user tekan tombol Escape
- Modal harus menutup saat user klik area overlay di luar modal content
- Semua pop-up konfirmasi harus menyertakan nama bulan/karyawan yang relevan agar HR tidak salah aksi
- Saat submit verifikasi berhasil, update hanya baris yang bersangkutan di tabel (bukan reload seluruh halaman)
- Pastikan kedua route web sudah didaftarkan di `routes/web.php` dalam group `role:hr`
