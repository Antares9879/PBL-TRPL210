# Prompt 1 — Halaman Dashboard HR (F13)

## Konteks Proyek

Kamu adalah Laravel 12 Fullstack Developer yang bekerja pada proyek **E-Outsourcing PT Ecogreen Oleochemicals Batam Plant** (PBL-TRPL210). Baca dan pahami file-file berikut sebelum mulai:

- `STRUKTUR-FOLDER.md` — panduan wajib struktur folder, konvensi penamaan, dan arsitektur proyek
- `resources/views/layouts/app.blade.php` — layout utama yang sudah ada
- `resources/views/super-admin/dashboard.blade.php` — contoh pola Blade shell (kosong, tanpa logika)
- `resources/js/super-admin/akun.js` — contoh pola JS: AJAX, CSRF, render tabel, paginasi
- `resources/css/super-admin.css` — contoh pola CSS per role
- `app/Http/Controllers/Api/HR/DashboardApiController.php` — controller API yang akan di-consume

Ikuti **semua** aturan di `STRUKTUR-FOLDER.md` tanpa pengecualian.

---

## Tugas

Buat halaman **Dashboard HR** (F13 — Monitoring Keseluruhan) dengan tiga file:

1. `resources/views/hr/dashboard.blade.php`
2. `resources/css/hr.css` *(buat dari awal atau tambahkan ke file yang sudah ada)*
3. `resources/js/hr/dashboard.js`

---

## Spesifikasi Blade (`hr/dashboard.blade.php`)

### Aturan Umum Blade
- Blade hanya bertugas sebagai **HTML shell** — tidak ada `@foreach`, tidak ada passing `$data` dari controller
- Semua data dimuat via AJAX dari JS setelah halaman terbuka
- Extends `layouts.app`, push CSS via `@push('styles')` dengan `@vite('resources/css/hr.css')`
- Push JS via `@push('scripts')` dengan `@vite('resources/js/hr/dashboard.js')`

### Struktur Layout
```
+--------------------------------------------------+
| SIDEBAR (desktop, fixed kiri)                    |
| Logo PT Ecogreen + nama role "HR Ecogreen"       |
| Menu navigasi F13–F16 (lihat bagian Sidebar)     |
+--------+-----------------------------------------+
| SIDEBAR | KONTEN UTAMA                            |
|         | Header: "Dashboard Monitoring"          |
|         | Sub-header: nama HR + tanggal hari ini  |
|         +-----------------------------------------+
|         | FILTER PERIODE (bulan + tahun)          |
|         +-----------------------------------------+
|         | STAT CARDS (baris 1 — 4 kartu)         |
|         | STAT CARDS (baris 2 — 3 kartu)         |
|         +-----------------------------------------+
|         | TABEL RINGKASAN PER DEPARTEMEN          |
|         +-----------------------------------------+
|         | TABEL ABSENSI TERBARU (7 hari terakhir) |
+--------------------------------------------------+
```

### Sidebar
- Fixed di kiri, lebar 240px di desktop
- Warna background: hijau tua PT Ecogreen (`#0D4726`)
- Logo PT Ecogreen di atas (gunakan `public/images/logo/logo-ecogreen.png`)
- Label role: "HR Ecogreen" dengan warna hijau muda
- Menu navigasi:
  - 🏠 Dashboard *(F13)* → `/hr/dashboard`
  - 📄 Verifikasi Dokumen *(F14)* → `/hr/dokumen`
  - 📊 Rekap Absensi *(F15)* → `/hr/rekap`
  - 🕵️ Audit Log *(F16)* → `/hr/audit`
  - Tombol Logout di bagian bawah sidebar
- Active state: background hijau lebih terang pada menu aktif
- Tidak ada bottom nav (HR hanya diakses via desktop)

### Elemen HTML Shell yang Wajib Ada
```html
<!-- Filter periode -->
<select id="filter-bulan">...</select>
<select id="filter-tahun">...</select>
<button id="btn-terapkan-filter">Terapkan</button>

<!-- Stat cards baris 1 -->
<div id="card-karyawan-aktif"></div>
<div id="card-total-perusahaan"></div>
<div id="card-total-departemen"></div>
<div id="card-hadir-hari-ini"></div>

<!-- Stat cards baris 2 -->
<div id="card-menunggu-absensi"></div>
<div id="card-menunggu-lembur"></div>
<div id="card-menunggu-izin"></div>

<!-- Tabel ringkasan departemen -->
<div id="tabel-ringkasan-departemen"></div>

<!-- Tabel absensi terbaru -->
<div id="filter-departemen-absensi"></div>
<div id="filter-perusahaan-absensi"></div>
<div id="tabel-absensi-terbaru"></div>
<div id="paginasi-absensi"></div>
```

---

## Spesifikasi CSS (`hr.css`)

### Palet Warna HR (Identitas PT Ecogreen)
```css
:root {
  --hr-hijau-tua:    #0D4726;
  --hr-hijau-utama:  #1A6E1A;
  --hr-hijau-muda:   #2ECC71;
  --hr-hijau-bg:     #EAF5EE;
  --hr-hijau-border: #AADDAA;
}
```

### Yang Harus Di-style
- `.hr-sidebar` — sidebar fixed dengan warna hijau tua
- `.hr-sidebar-menu-item` — item menu dengan hover dan active state
- `.hr-stat-card` — kartu statistik dengan border kiri hijau sebagai aksen
- `.hr-stat-card .nilai` — angka besar (font-size: 28px, font-weight: 500)
- `.hr-stat-card .label` — label kecil (font-size: 12px, warna muted)
- `.hr-badge-hadir` / `.hr-badge-izin` / `.hr-badge-alpa` / `.hr-badge-pending` — badge status kehadiran dengan warna berbeda
- `.hr-badge-menunggu` / `.hr-badge-disetujui` / `.hr-badge-ditolak` — badge status validasi
- `.hr-tabel` — tabel dengan header hijau muda dan baris zebra stripe
- `.hr-progress-bar` — progress bar hijau untuk persentase kehadiran di tabel departemen
- Loading skeleton animation untuk stat cards saat data belum dimuat

### Responsif
- Sidebar collapse menjadi icon-only di viewport < 1024px
- Konten utama memiliki `margin-left` yang menyesuaikan lebar sidebar

---

## Spesifikasi JS (`hr/dashboard.js`)

### Aturan Umum JS
- Vanilla JS (bukan jQuery) — sesuai pola di `resources/js/super-admin/`
- CSRF token diambil dari `<meta name="csrf-token">` via `$.ajaxSetup` atau fetch header
- Semua request ke API menggunakan `fetch()` dengan header `X-CSRF-TOKEN`
- Pisahkan fungsi: `loadStats()`, `loadRingkasan()`, `loadAbsensi()`, `renderTabel()`, `renderPaginasi()`

### Endpoint API yang Digunakan
```
GET /api/hr/dashboard/stats?bulan=&tahun=
GET /api/hr/dashboard/ringkasan?bulan=&tahun=
GET /api/hr/dashboard/absensi?bulan=&tahun=&id_departemen=&id_perusahaan=&page=
GET /api/hr/dashboard/filter-options
```

### Alur Kerja JS
1. Saat halaman load → jalankan `initFilterPeriode()` (isi dropdown bulan & tahun, set default ke bulan & tahun saat ini)
2. Jalankan `loadFilterOptions()` → isi dropdown departemen & perusahaan untuk filter tabel absensi
3. Jalankan `loadStats(bulan, tahun)` → render 7 stat cards
4. Jalankan `loadRingkasan(bulan, tahun)` → render tabel ringkasan departemen
5. Jalankan `loadAbsensi(params)` → render tabel absensi + paginasi
6. Event listener pada `#btn-terapkan-filter` → re-run semua fungsi load dengan parameter baru
7. Event listener pada filter departemen & perusahaan → re-run `loadAbsensi()` saja

### Render Stat Cards
```javascript
function renderStatCard(idElement, nilai, label, ikonClass, warnaBadge) {
  // Tampilkan: ikon, nilai angka, label teks
  // Jika nilai adalah persentase, tampilkan dengan "%" suffix
  // Animasi counter dari 0 ke nilai saat pertama kali load
}
```

### Render Tabel Ringkasan Departemen
Kolom: Departemen | Jumlah Karyawan | Hadir | Izin | Alpa | % Kehadiran | Total Menit Lembur

- Kolom "% Kehadiran" menggunakan `.hr-progress-bar`
- Baris dengan % kehadiran < 70% diberi warna latar merah muda sebagai warning
- Tidak perlu paginasi (data per departemen terbatas)

### Render Tabel Absensi
Kolom: Tanggal | Nama Karyawan | Departemen | Perusahaan | Shift | Check In | Check Out | Status Kehadiran | Status Validasi

- Status menggunakan badge class dari CSS (`.hr-badge-hadir`, dll.)
- Paginasi mengikuti pola dari `STRUKTUR-FOLDER.md` (tombol Prev/Next + info halaman)
- Saat loading tampilkan skeleton row sebagai placeholder

### Error Handling
- Jika API return `status: false` → tampilkan pesan error di dalam container tabel
- Jika network error → tampilkan toast notification merah di pojok kanan atas
- Timeout request: 10 detik

---

## Catatan Tambahan
- Ikuti konvensi penamaan dari `STRUKTUR-FOLDER.md`: file JS kebab-case, class PHP PascalCase, dll.
- Pastikan `dashboard.js` didaftarkan di `vite.config.js` input array
- Tambahkan `@vite('resources/css/hr.css')` di `vite.config.js`
- HR PageController sudah ada di `app/Http/Controllers/HR/PageController.php` — pastikan method `dashboard()` me-return `view('hr.dashboard')`
