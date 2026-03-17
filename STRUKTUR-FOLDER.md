# 📁 Panduan Struktur Folder — PBL-TRPL210
> **Proyek:** Aplikasi E-Outsourcing PT Ecogreen Oleochemicals Batam Plant  
> **Framework:** Laravel 12 (PHP 8.2+)  
> **Anggota Tim:** William · Kelvin · Rafly · Rivaldi · Akut  
> ⚠️ **Baca dokumen ini sebelum mulai menulis kode!**

---

## 🏛️ Keputusan Arsitektur

Sebelum membaca struktur folder, pahami dulu keputusan arsitektur yang sudah disepakati tim:

| Keputusan | Pilihan | Alasan |
|---|---|---|
| Rendering | Blade hanya sebagai HTML shell | Frontend murni JS + AJAX, tidak perlu tau proses backend |
| Autentikasi | Custom manual + Laravel Session | Breeze/Jetstream terlalu opinionated untuk setup AJAX |
| API Format | JSON selalu dengan field `status`, `message`, `data` | Konsistensi response agar JS tidak perlu handle berbagai format |
| Validasi | `FormRequest` class terpisah | Agar controller tetap bersih |
| Storage dokumen | Local storage (`storage/app/private/`) | Cukup untuk skala PBL, akses wajib lewat controller |
| Aset statis | `public/images/` | File publik, tidak perlu lewat Laravel |
| CSRF | Token dikirim di setiap header AJAX | Wajib untuk semua request POST/PUT/DELETE |
| CSS Framework | Tailwind CSS | Default Laravel 12, cocok dengan arsitektur HTML shell |
| Pemisahan CSS | Per role di `resources/css/` | CSS spesifik hanya dimuat di halaman yang relevan |
| Paginasi | `paginate(20)` di ApiController + render di JS | Untuk halaman dengan data yang tumbuh terus |

### Format JSON Response (WAJIB diikuti semua API Controller)

```json
{
    "status": true,
    "message": "Check-in berhasil dicatat.",
    "data": { ... }
}
```

```json
{
    "status": false,
    "message": "Lokasi tidak valid. Anda berada di luar area PT Ecogreen.",
    "data": null
}
```

### Konvensi CSRF pada AJAX (WAJIB di semua file JS)

```javascript
// Tambahkan di setiap file JS, satu kali di bagian atas
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

```html
<!-- Tambahkan di layout app.blade.php, di dalam <head> -->
<meta name="csrf-token" content="{{ csrf_token() }}">
```

---

## 🗂️ Struktur Folder Root

```
PBL-TRPL210/
├── app/
│   ├── Exceptions/                 ← Handle error terpusat (return JSON)
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/               ← FormRequest untuk validasi
│   ├── Models/
│   ├── Providers/
│   └── Services/                   ← BUAT MANUAL, tidak ada secara default
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/
│   └── images/                     ← Aset statis publik (logo, foto perusahaan)
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
│   ├── web.php                     ← Hanya serve halaman HTML per role
│   └── api.php                     ← Semua logika bisnis, return JSON
├── storage/
│   └── app/
│       └── private/
│           └── dokumen-izin/       ← Upload dokumen, akses wajib lewat controller
├── tests/
├── vendor/
├── .env
├── .env.example
├── artisan
├── composer.json
├── STRUKTUR-FOLDER.md
└── vite.config.js
```

---

## 📂 Detail: `app/Http/Controllers/`

Controller dibagi dua jenis:
- **Page Controller** — hanya return Blade view (HTML shell kosong)
- **API Controller** — handle semua logika, return JSON

```
app/Http/Controllers/
│
├── Auth/
│   └── AuthController.php                  → Login, logout (session), return JSON
│
├── — PAGE CONTROLLERS (return Blade) —
│
├── SuperAdmin/
│   └── PageController.php                  → Serve halaman-halaman Super Admin
├── AdminOutsource/
│   └── PageController.php                  → Serve halaman-halaman Admin Outsource
├── UserDepartemen/
│   └── PageController.php                  → Serve halaman-halaman User Departemen
├── HR/
│   └── PageController.php                  → Serve halaman-halaman HR
├── Karyawan/
│   └── PageController.php                  → Serve halaman-halaman Karyawan
│
└── — API CONTROLLERS (return JSON) —
    └── Api/
        ├── Auth/
        │   └── AuthApiController.php           → Login, logout via AJAX
        │
        ├── SuperAdmin/
        │   ├── AkunApiController.php            → Kelola akun pengguna (F17)
        │   ├── MasterDataApiController.php      → Kelola departemen & perusahaan (F18)
        │   └── KonfigurasiAreaApiController.php → Atur radius GPS (F19)
        │
        ├── AdminOutsource/
        │   ├── KaryawanApiController.php        → CRUD karyawan outsource (F07)
        │   ├── PlanningKerjaApiController.php   → Input & upload planning (F08, F09)
        │   └── ValidasiAbsensiApiController.php → Validasi kehadiran (F10, F11)
        │
        ├── UserDepartemen/
        │   └── ValidasiLemburApiController.php  → Approve/Reject lembur (F12)
        │
        ├── HR/
        │   ├── DashboardApiController.php       → Data monitoring keseluruhan (F13)
        │   ├── DokumenApiController.php         → Verifikasi dokumen (F14)
        │   └── RekapApiController.php           → Rekap bulanan & audit (F15, F16)
        │
        └── Karyawan/
            ├── AbsensiApiController.php         → Check-in & check-out GPS (F01)
            ├── JadwalApiController.php          → Ambil data jadwal kerja (F02)
            ├── LemburApiController.php          → Pengajuan lembur H+1 (F03)
            ├── IzinApiController.php            → Pengajuan izin (F04)
            ├── DokumenIzinApiController.php     → Upload dokumen izin (F05)
            └── RiwayatAbsensiApiController.php  → Rekap absensi pribadi (F06)
```

---

## 📂 Detail: `app/Http/Requests/`

Satu FormRequest per aksi yang butuh validasi. Pisahkan dari controller agar controller tetap bersih.

```
app/Http/Requests/
├── Auth/
│   └── LoginRequest.php
├── Karyawan/
│   ├── CheckInRequest.php          → Validasi: koordinat GPS wajib ada & valid
│   ├── CheckOutRequest.php
│   ├── StoreLemburRequest.php      → Validasi: tanggal tidak melewati H+1
│   ├── StoreIzinRequest.php
│   └── UploadDokumenRequest.php    → Validasi: tipe file (pdf/png/xls), maks. ukuran
├── AdminOutsource/
│   ├── StoreKaryawanRequest.php
│   ├── UpdateKaryawanRequest.php
│   └── StorePlanningRequest.php
├── SuperAdmin/
│   ├── StoreAkunRequest.php
│   └── StoreKonfigurasiAreaRequest.php
└── HR/
    └── GenerateRekapRequest.php
```

---

## 📂 Detail: `app/Models/`

```
app/Models/
│
├── — Master Data —
├── PerusahaanOutsource.php         → Tabel: perusahaan_outsource
├── Departemen.php                  → Tabel: departemen
├── KonfigurasiArea.php             → Tabel: konfigurasi_area
├── Shift.php                       → Tabel: shift
├── JenisIzin.php                   → Tabel: jenis_izin
│
├── — Pengguna & Profil (Table Per Type) —
├── Pengguna.php                    → Tabel: pengguna (autentikasi utama, semua role)
├── AdminOutsourceProfile.php       → Tabel: admin_outsource_profile (extension 1:1)
├── UserDepartemenProfile.php       → Tabel: user_departemen_profile (extension 1:1)
├── Karyawan.php                    → Tabel: karyawan (extension 1:1 + profil lengkap)
│
├── — Planning & Jadwal —
├── PlanningKerja.php               → Tabel: planning_kerja
├── JadwalKerja.php                 → Tabel: jadwal_kerja
│
├── — Absensi & Pengajuan —
├── Absensi.php                     → Tabel: absensi
├── PengajuanLembur.php             → Tabel: pengajuan_lembur
├── PengajuanIzin.php               → Tabel: pengajuan_izin
├── DokumenIzin.php                 → Tabel: dokumen_izin
│
└── — Log & Rekapitulasi —
    ├── AuditLog.php                → Tabel: audit_log
    ├── Notifikasi.php              → Tabel: notifikasi
    └── RekapBulanan.php            → Tabel: rekap_bulanan
```

> **Catatan:** Nama class menggunakan `Pengguna` (bukan `User`). Wajib tulis `protected $table = 'pengguna'` di dalam model tersebut.

---

## 📂 Detail: `app/Services/`

> ⚠️ **Buat folder ini secara manual** — tidak tersedia secara default di Laravel.

```
app/Services/
├── AbsensiService.php              → Hitung menit telat, menit kerja normal, menit lembur pending
├── GpsValidationService.php        → Hitung jarak koordinat vs radius area (Haversine formula)
├── LemburService.php               → Validasi batas H+1, hitung menit lembur resmi
├── PlanningService.php             → Generate jadwal_kerja dari data planning_kerja
├── RekapService.php                → Agregasi data bulanan ke tabel rekap_bulanan
└── NotifikasiService.php           → Tulis ke tabel notifikasi setelah perubahan status
```

---

## 📂 Detail: `app/Exceptions/`

File `Handler.php` sudah ada secara default. Modifikasi agar semua error yang terjadi di route `api/*` selalu return JSON, bukan halaman HTML error.

```
app/Exceptions/
└── Handler.php                     → Override render() untuk return JSON di semua route api/*
```

Contoh pola yang digunakan:

```php
// Di dalam Handler.php
public function render($request, Throwable $e)
{
    if ($request->is('api/*')) {
        return response()->json([
            'status'  => false,
            'message' => $e->getMessage(),
            'data'    => null
        ], $this->getStatusCode($e));
    }
    return parent::render($request, $e);
}
```

---

## 📂 Detail: `app/Http/Middleware/`

```
app/Http/Middleware/
├── RoleMiddleware.php              → Cek role pengguna, return JSON 403 jika tidak sesuai
└── CheckGpsRequest.php            → Validasi koordinat GPS wajib ada di request absensi
```

---

## 📂 Detail: `resources/views/`

Blade hanya bertugas sebagai **HTML shell**. Tidak ada logika bisnis, tidak ada looping data di Blade. Data selalu dimuat via AJAX setelah halaman terbuka.

```
resources/views/
│
├── layouts/
│   ├── app.blade.php               → Layout utama: <meta csrf-token>, link CSS/JS
│   └── guest.blade.php             → Layout tamu: halaman login
│
├── auth/
│   └── login.blade.php             → Form login (submit via AJAX)
│
├── components/
│   ├── navbar.blade.php
│   ├── sidebar.blade.php
│   └── modal-konfirmasi.blade.php  → Modal approve/reject (konten diisi JS)
│
├── super-admin/
│   ├── dashboard.blade.php
│   ├── akun.blade.php
│   ├── master-data.blade.php
│   └── konfigurasi-area.blade.php
│
├── admin-outsource/
│   ├── dashboard.blade.php
│   ├── karyawan.blade.php
│   ├── planning.blade.php
│   └── validasi-absensi.blade.php
│
├── user-departemen/
│   ├── dashboard.blade.php
│   └── validasi-lembur.blade.php
│
├── hr/
│   ├── dashboard.blade.php
│   ├── dokumen.blade.php
│   ├── rekap.blade.php
│   └── audit.blade.php
│
└── karyawan/
    ├── dashboard.blade.php
    ├── absensi.blade.php
    ├── jadwal.blade.php
    ├── lembur.blade.php
    ├── izin.blade.php
    └── riwayat.blade.php
```

---

## 📂 Detail: `resources/css/`

CSS dipisah per role agar setiap halaman hanya memuat CSS yang relevan, tidak membebani halaman dengan style yang tidak dipakai.

```
resources/css/
├── app.css             → CSS global: warna, font, komponen yang dipakai semua role
├── karyawan.css        → CSS khusus halaman-halaman karyawan
├── admin.css           → CSS khusus halaman-halaman admin outsource
├── departemen.css      → CSS khusus halaman user departemen
├── hr.css              → CSS khusus halaman-halaman HR
└── super-admin.css     → CSS khusus halaman-halaman super admin
```

Daftarkan semua file di `vite.config.js`:

```js
// vite.config.js
export default defineConfig({
    plugins: [laravel({
        input: [
            'resources/css/app.css',
            'resources/css/karyawan.css',
            'resources/css/admin.css',
            'resources/css/departemen.css',
            'resources/css/hr.css',
            'resources/css/super-admin.css',
            'resources/js/app.js',
        ],
        refresh: true,
    })],
});
```

Di `layouts/app.blade.php`, tambahkan slot untuk CSS tambahan per halaman:

```blade
@vite('resources/css/app.css')
@stack('styles')
```

Di setiap halaman Blade, inject CSS role yang sesuai:

```blade
{{-- contoh di karyawan/absensi.blade.php --}}
@extends('layouts.app')

@push('styles')
    @vite('resources/css/karyawan.css')
@endpush
```

### Aturan penulisan CSS

- `app.css` — hanya untuk style yang benar-benar dipakai di lebih dari satu role
- CSS per role tidak boleh saling import satu sama lain
- Tailwind utility class ditulis langsung di Blade, **bukan** di file CSS
- File CSS digunakan untuk style kustom yang tidak bisa dicapai dengan Tailwind saja

---

## 📂 Detail: `resources/js/`

File JS dikelompokkan per role dan per fitur. Setiap file bertanggung jawab atas satu halaman.

```
resources/js/
│
├── bootstrap.js                    → Setup global: CSRF token header, konfigurasi jQuery AJAX
├── app.js                          → Entry point utama (import bootstrap.js)
│
├── auth/
│   └── login.js                    → Handle form login via AJAX
│
├── super-admin/
│   ├── akun.js
│   ├── master-data.js
│   └── konfigurasi-area.js
│
├── admin-outsource/
│   ├── karyawan.js
│   ├── planning.js
│   └── validasi-absensi.js
│
├── user-departemen/
│   └── validasi-lembur.js
│
├── hr/
│   ├── dashboard.js
│   ├── dokumen.js
│   ├── rekap.js
│   └── audit.js
│
└── karyawan/
    ├── absensi.js                  → Request GPS browser, kirim koordinat via AJAX
    ├── jadwal.js
    ├── lembur.js
    ├── izin.js
    └── riwayat.js
```

---

## 🎨 Tailwind CSS — Panduan Penggunaan

Tailwind CSS adalah CSS framework default Laravel 12. Utility class ditulis langsung di Blade, bukan di file CSS.

### Konfigurasi `tailwind.config.js`

Karena konten HTML juga di-generate oleh JS (AJAX), path JS wajib ditambahkan agar Tailwind tidak membuang class yang dipakai di JS:

```js
// tailwind.config.js
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',       // ← wajib, untuk class yang ditulis di JS
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}
```

### Aturan penggunaan Tailwind di proyek ini

- Utility class Tailwind ditulis di file **Blade** untuk elemen statis
- Untuk elemen yang dibuat dinamis oleh JS, pastikan class-nya juga muncul di file **JS** agar ter-scan oleh Tailwind
- Hindari menulis `style=""` inline — gunakan utility class Tailwind
- Gunakan file CSS di `resources/css/` hanya untuk style yang **tidak bisa** dicapai dengan Tailwind

---

## 📂 Detail: `public/images/`

Khusus untuk aset statis yang boleh diakses publik langsung lewat URL.

```
public/images/
├── logo/
│   └── logo-ecogreen.png
├── perusahaan/                     → Foto/logo perusahaan outsource
└── default/
    └── avatar.png                  → Foto profil default
```

---

## 📂 Detail: `storage/app/private/dokumen-izin/`

Khusus untuk dokumen upload yang bersifat sensitif. **Tidak bisa diakses lewat URL langsung** — wajib melalui controller yang memverifikasi hak akses terlebih dahulu.

```
storage/app/private/
└── dokumen-izin/
    └── {id_izin}/                  → Satu folder per pengajuan izin
        ├── surat-dokter.pdf
        └── bukti-lainnya.png
```

Cara akses yang benar lewat controller:

```php
// Di DokumenIzinApiController.php
public function download($id)
{
    $dokumen = DokumenIzin::findOrFail($id);

    // Verifikasi hak akses dulu sebelum download
    $this->authorize('view', $dokumen);

    return Storage::download($dokumen->path_file, $dokumen->nama_file);
}
```

---

## 📂 Detail: `database/migrations/`

Urutan wajib mengikuti dependency FK antar tabel.

```
database/migrations/
│
├── — Tahap 1: Tabel tanpa FK —
├── xxxx_create_perusahaan_outsource_table.php
├── xxxx_create_departemen_table.php
├── xxxx_create_shift_table.php
├── xxxx_create_jenis_izin_table.php
├── xxxx_create_konfigurasi_area_table.php      ← FK ke pengguna ditambahkan di tahap 3
│
├── — Tahap 2: Tabel pengguna —
├── xxxx_create_pengguna_table.php
│
├── — Tahap 3: Extension tables & FK ke pengguna —
├── xxxx_create_admin_outsource_profile_table.php
├── xxxx_create_user_departemen_profile_table.php
├── xxxx_create_karyawan_table.php
├── xxxx_add_fk_diubah_oleh_to_konfigurasi_area.php
│
├── — Tahap 4: Planning & jadwal —
├── xxxx_create_planning_kerja_table.php
├── xxxx_create_jadwal_kerja_table.php
│
├── — Tahap 5: Absensi & pengajuan —
├── xxxx_create_absensi_table.php
├── xxxx_create_pengajuan_lembur_table.php
├── xxxx_create_pengajuan_izin_table.php
├── xxxx_create_dokumen_izin_table.php
│
└── — Tahap 6: Log & rekap —
    ├── xxxx_create_audit_log_table.php
    ├── xxxx_create_notifikasi_table.php
    └── xxxx_create_rekap_bulanan_table.php
```

---

## 📂 Detail: `database/seeders/`

```
database/seeders/
├── DatabaseSeeder.php              → Memanggil semua seeder secara berurutan
├── ShiftSeeder.php                 → 4 shift default: Pagi, Siang, Malam, Normal
├── JenisIzinSeeder.php             → 4 jenis izin: Sakit, Keluarga, Lainnya, Cuti
├── DepartemenSeeder.php            → Data departemen PT Ecogreen
├── PerusahaanOutsourceSeeder.php   → Data perusahaan outsource contoh
├── KonfigurasiAreaSeeder.php       → Koordinat & radius area PT Ecogreen
└── PenggunaSeeder.php              → Akun testing: 1 per role, lengkap dengan profil
```

---

## 📂 Detail: `routes/`

```
routes/
├── web.php     → Hanya serve halaman HTML per role (return Blade)
└── api.php     → Semua logika bisnis, selalu return JSON
```

Pola `web.php` — hanya serve halaman:

```php
Route::middleware('auth')->group(function () {
    Route::middleware('role:super_admin')->prefix('super-admin')->group(function () {
        Route::get('dashboard', [SuperAdmin\PageController::class, 'dashboard']);
        Route::get('akun',      [SuperAdmin\PageController::class, 'akun']);
        // ... halaman lainnya
    });

    Route::middleware('role:karyawan')->prefix('karyawan')->group(function () {
        Route::get('absensi',   [Karyawan\PageController::class, 'absensi']);
        Route::get('jadwal',    [Karyawan\PageController::class, 'jadwal']);
        // ... halaman lainnya
    });
    // ... role lainnya
});
```

Pola `api.php` — semua logika, return JSON:

```php
Route::middleware('auth')->group(function () {
    Route::middleware('role:super_admin')->prefix('super-admin')->group(function () {
        Route::apiResource('akun',              Api\SuperAdmin\AkunApiController::class);
        Route::apiResource('master-data',       Api\SuperAdmin\MasterDataApiController::class);
        Route::apiResource('konfigurasi-area',  Api\SuperAdmin\KonfigurasiAreaApiController::class);
    });

    Route::middleware('role:admin_outsource')->prefix('admin')->group(function () {
        Route::apiResource('karyawan',          Api\AdminOutsource\KaryawanApiController::class);
        Route::apiResource('planning',          Api\AdminOutsource\PlanningKerjaApiController::class);
        Route::apiResource('validasi-absensi',  Api\AdminOutsource\ValidasiAbsensiApiController::class);
    });

    Route::middleware('role:user_departemen')->prefix('departemen')->group(function () {
        Route::apiResource('validasi-lembur',   Api\UserDepartemen\ValidasiLemburApiController::class);
    });

    Route::middleware('role:hr')->prefix('hr')->group(function () {
        Route::apiResource('rekap',             Api\HR\RekapApiController::class);
        Route::apiResource('dokumen',           Api\HR\DokumenApiController::class);
        Route::get('audit',                     [Api\HR\DashboardApiController::class, 'audit']);
    });

    Route::middleware('role:karyawan')->prefix('karyawan')->group(function () {
        Route::post('check-in',                 [Api\Karyawan\AbsensiApiController::class, 'checkIn']);
        Route::post('check-out',                [Api\Karyawan\AbsensiApiController::class, 'checkOut']);
        Route::apiResource('lembur',            Api\Karyawan\LemburApiController::class);
        Route::apiResource('izin',              Api\Karyawan\IzinApiController::class);
        Route::post('izin/{izin}/dokumen',      [Api\Karyawan\DokumenIzinApiController::class, 'upload']);
        Route::get('riwayat',                   [Api\Karyawan\RiwayatAbsensiApiController::class, 'index']);
        Route::get('jadwal',                    [Api\Karyawan\JadwalApiController::class, 'index']);
    });
});
```

---

## 📄 Aturan Paginasi

### Halaman yang WAJIB pakai paginasi

| Halaman | Alasan |
|---|---|
| `karyawan/riwayat.blade.php` | Record absensi tumbuh setiap hari |
| `hr/audit.blade.php` | Setiap aksi approve/reject menulis satu entri |
| `hr/rekap.blade.php` | Menampilkan data semua karyawan semua perusahaan |
| `admin-outsource/karyawan.blade.php` | Jumlah karyawan bisa terus bertambah |
| `admin-outsource/validasi-absensi.blade.php` | Data absensi harian seluruh karyawan |
| `user-departemen/validasi-lembur.blade.php` | Pengajuan lembur bisa menumpuk |
| `hr/dokumen.blade.php` | Dokumen izin dari semua karyawan |

### Halaman yang TIDAK perlu paginasi

| Halaman | Alasan |
|---|---|
| `karyawan/jadwal.blade.php` | Sudah dibatasi per bulan secara natural |
| `super-admin/konfigurasi-area.blade.php` | Data sedikit dan jarang berubah |
| `super-admin/master-data.blade.php` | Jumlah departemen & shift terbatas |

### Implementasi paginasi

**Di ApiController** — gunakan `paginate()` bukan `get()`:

```php
// RiwayatAbsensiApiController.php
public function index(Request $request)
{
    $data = Absensi::where('id_karyawan', auth()->id())
                   ->orderBy('tanggal_absensi', 'desc')
                   ->paginate(20);

    return response()->json(['status' => true, 'message' => 'OK', 'data' => $data]);
}
```

Laravel otomatis menambahkan metadata paginasi di dalam response JSON:

```json
{
    "status": true,
    "message": "OK",
    "data": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 98,
        "data": [ ... ]
    }
}
```

**Di file JS** — terima metadata dan render tombol navigasi:

```javascript
// riwayat.js
let currentPage = 1;

function loadRiwayat(page = 1) {
    $.get(`/api/karyawan/riwayat?page=${page}`, function(res) {
        renderTabel(res.data.data);
        renderPaginasi(res.data);
        currentPage = res.data.current_page;
    });
}

function renderPaginasi(meta) {
    const prev = meta.current_page > 1;
    const next = meta.current_page < meta.last_page;

    $('#paginasi').html(`
        <button ${prev ? '' : 'disabled'} onclick="loadRiwayat(${meta.current_page - 1})">Prev</button>
        <span>Halaman ${meta.current_page} dari ${meta.last_page}</span>
        <button ${next ? '' : 'disabled'} onclick="loadRiwayat(${meta.current_page + 1})">Next</button>
    `);
}

loadRiwayat();
```

**Di Blade** — cukup sediakan container kosong:

```blade
<div id="tabel-container"></div>
<div id="paginasi"></div>
```

---

## 📝 Konvensi Penamaan Tim

| Tipe File            | Format                        | Contoh                              |
|----------------------|-------------------------------|-------------------------------------|
| Page Controller      | PascalCase + `Controller`     | `PageController.php`                |
| API Controller       | PascalCase + `ApiController`  | `AbsensiApiController.php`          |
| Model                | PascalCase singular           | `PengajuanLembur.php`               |
| Service              | PascalCase + `Service`        | `AbsensiService.php`                |
| FormRequest          | Aksi + `Request`              | `CheckInRequest.php`                |
| Migration            | snake_case auto-generated     | `create_planning_kerja_table.php`   |
| Seeder               | PascalCase + `Seeder`         | `PenggunaSeeder.php`                |
| View Blade           | kebab-case                    | `validasi-absensi.blade.php`        |
| File JS              | kebab-case per halaman        | `validasi-lembur.js`                |
| Nama route API       | prefix.entitas.aksi           | `api/karyawan/check-in`             |
| Variable PHP         | camelCase                     | `$dataPlanningKerja`                |
| Nama tabel DB        | snake_case plural             | `pengajuan_lembur`                  |
| Kolom DB             | snake_case                    | `waktu_check_in`                    |

---

## 🔐 Daftar Role & Akses

| Role              | Deskripsi                                              | Prefix Web URL | Prefix API URL       |
|-------------------|--------------------------------------------------------|----------------|----------------------|
| `super_admin`     | Staff IT PT Ecogreen — kelola akun & master data       | `/super-admin` | `/api/super-admin`   |
| `hr`              | HR PT Ecogreen — monitoring, rekap & audit             | `/hr`          | `/api/hr`            |
| `user_departemen` | Perwakilan departemen — validasi lembur karyawan       | `/departemen`  | `/api/departemen`    |
| `admin_outsource` | Admin perusahaan outsource — planning & validasi absen | `/admin`       | `/api/admin`         |
| `karyawan`        | Karyawan outsource — absensi GPS & pengajuan           | `/karyawan`    | `/api/karyawan`      |

---

## ⚠️ File yang TIDAK Boleh Diubah Sembarangan

| File / Folder               | Alasan                                                        |
|-----------------------------|---------------------------------------------------------------|
| `.env`                      | Berisi kredensial sensitif. Setiap developer punya `.env` sendiri |
| `vendor/`                   | Di-manage Composer. Gunakan `composer install`               |
| `bootstrap/cache/`          | Cache otomatis. Bersihkan dengan `php artisan cache:clear`   |
| `composer.lock`             | Jangan diedit manual. Update via `composer update`           |
| Migration yang sudah jalan  | Buat migration `ALTER` baru, jangan edit file lama           |
| `storage/app/private/`      | Jangan expose ke publik. Akses hanya lewat controller        |

---

## ⚡ Perintah Artisan yang Sering Dipakai

```bash
# Generate file baru
php artisan make:controller Api/Karyawan/AbsensiApiController --api
php artisan make:model PengajuanLembur -m        # sekaligus buat migration
php artisan make:request Karyawan/CheckInRequest
php artisan make:seeder PenggunaSeeder
php artisan make:middleware RoleMiddleware

# Database
php artisan migrate
php artisan migrate:fresh --seed                 # reset dan isi ulang data dummy
php artisan db:seed --class=PenggunaSeeder

# Utilitas
php artisan route:list                           # lihat semua route terdaftar
php artisan cache:clear                          # bersihkan cache
php artisan storage:link                         # wajib dijalankan sekali untuk symlink storage
```

---

## 📌 Catatan Penting Khusus Proyek Ini

1. **Folder `app/Services/` wajib dibuat manual** — tidak tersedia secara default di Laravel.
2. **Model utama adalah `Pengguna`**, bukan `User`. Wajib tulis `protected $table = 'pengguna'` di dalam model.
3. **Blade tidak boleh berisi logika data** — tidak ada `@foreach`, tidak ada passing `$data` dari controller ke Blade. Semua data dimuat via AJAX setelah halaman terbuka.
4. **Semua API wajib return format JSON konsisten** — `status`, `message`, `data`. Tidak boleh ada controller yang return format berbeda.
5. **CSRF token wajib dikirim** di setiap request AJAX POST/PUT/DELETE. Setup sekali di `bootstrap.js`, berlaku global.
6. **Upload dokumen izin** disimpan di `storage/app/private/dokumen-izin/{id_izin}/` dan diakses hanya lewat controller yang memverifikasi hak akses.
7. **Logika GPS (Haversine)** diletakkan di `GpsValidationService.php`, bukan di Controller.
8. **Setiap aksi approve/reject** wajib menulis entri ke tabel `audit_log` — implementasikan di Service, bukan Controller.
9. **Batas lembur retroaktif H+1** divalidasi di `LemburService`. Pengajuan melewati batas otomatis berstatus `kadaluarsa`.
10. **Tailwind utility class** ditulis di Blade dan JS, bukan di file CSS. File CSS di `resources/css/` hanya untuk style kustom yang tidak bisa dicapai Tailwind.
11. **Path `resources/js/`** wajib didaftarkan di `tailwind.config.js` agar class yang ditulis di JS tidak dibuang saat build.
12. **Halaman dengan data yang tumbuh** (riwayat absensi, audit log, daftar karyawan, dll.) wajib menggunakan `paginate()` di ApiController — jangan gunakan `get()` atau `all()`.

---

> 💡 **Tips VS Code:** Install ekstensi **Markdown Preview Enhanced** lalu tekan `Ctrl+Shift+V` untuk membuka preview dokumen ini dalam tampilan yang rapi.
