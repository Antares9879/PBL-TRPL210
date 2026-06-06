# Prompt: Fitur Notifikasi In-App — Role HR

## Konteks Proyek

Proyek ini adalah aplikasi **E-Outsourcing PT Ecogreen Oleochemicals** berbasis Laravel 12
dengan arsitektur **Blade sebagai HTML shell + JS/AJAX murni** (tidak ada logika data di Blade).
Semua data dimuat via AJAX setelah halaman terbuka. Baca `STRUKTUR-FOLDER.md` sebelum menulis
kode apapun.

---

## Tujuan Task

Implementasikan fitur **notifikasi in-app** untuk role **HR** yang terdiri dari:

1. **Panel overlay notifikasi** — muncul saat tombol lonceng di topbar diklik
2. **Halaman "Lihat Semua" notifikasi** — halaman penuh dengan paginasi 25 per halaman
3. **Badge count** di ikon lonceng — diperbarui via polling setiap 30 detik

---

## File yang Harus Dibuat / Dimodifikasi

### File Baru

| File | Keterangan |
|---|---|
| `resources/views/hr/notifikasi.blade.php` | Halaman penuh "Lihat Semua" notifikasi |
| `resources/js/hr/notifikasi.js` | Logic JS untuk panel overlay + halaman penuh |

### File yang Dimodifikasi

| File | Perubahan |
|---|---|
| `resources/views/layouts/app.blade.php` | Tambah markup panel overlay (kondisional untuk role hr) |
| `resources/css/hr.css` | Tambah styling panel overlay + halaman notifikasi |
| `app/Http/Controllers/HR/PageController.php` | Tambah method `notifikasi()` |
| `routes/web.php` | Tambah route `GET /hr/notifikasi` |
| `vite.config.js` | Daftarkan `resources/js/hr/notifikasi.js` sebagai entry point |

> Sidebar nav HR ada langsung di dalam masing-masing blade file HR (inline, bukan partial
> terpisah). Tambahkan link "Notifikasi" di **setiap file blade HR** yang memiliki
> `@section('sidebar-nav')`: `dashboard.blade.php`, `dokumen.blade.php`, `rekap.blade.php`,
> `rekap-detail.blade.php`, dan `audit.blade.php`.

---

## Perhatian: Layout yang Digunakan

Role HR menggunakan `layouts/app.blade.php` — **file yang sama** dengan User Departemen
dan Super Admin. Oleh karena itu:

- Markup panel overlay yang ditambahkan ke `layouts/app.blade.php` harus menggunakan
  kondisi Blade yang **mencakup kedua role** yang sudah ada dan role baru:

```blade
@if(auth()->check() && in_array(auth()->user()->role, ['user_departemen', 'hr']))
    <!-- markup panel overlay -->
@endif
```

- Jika role User Departemen sudah menambahkan blok ini lebih dulu (dari prompt sebelumnya),
  cukup **perluas kondisi** dengan menambahkan `'hr'` ke array, jangan duplikasi markup.
- CSS HR ada di `resources/css/hr.css` — dimuat via `@push('styles')` di setiap blade HR.

---

## Spesifikasi API yang Digunakan

Semua endpoint sudah tersedia. Semua request wajib menyertakan `X-CSRF-TOKEN`.

### 1. Ambil daftar notifikasi (panel — 4 terbaru)
```
GET /api/notifikasi?per_page=4
```
Response:
```json
{
  "status": true,
  "message": "Notifikasi berhasil dimuat.",
  "data": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 4,
    "total": 10,
    "data": [
      {
        "id_notifikasi": 1,
        "judul": "Dokumen izin Budi Santoso belum lengkap",
        "isi": "HR menemukan kekurangan dokumen pada pengajuan izin.",
        "jenis": "izin",
        "id_referensi": 22,
        "is_dibaca": false,
        "dibaca_pada": null,
        "created_at": "2025-06-13T09:00:00.000000Z"
      }
    ]
  }
}
```

### 2. Jumlah belum dibaca (badge)
```
GET /api/notifikasi/jumlah-baru
```

### 3. Tandai satu dibaca
```
PATCH /api/notifikasi/{id}/baca
```

### 4. Tandai semua dibaca
```
PATCH /api/notifikasi/baca-semua
```

### 5. Semua notifikasi dengan paginasi (halaman penuh)
```
GET /api/notifikasi?per_page=25&page={n}
```

---

## Spesifikasi Fitur Detail

### A. Panel Overlay Notifikasi

**Trigger:** Klik tombol lonceng di topbar. Di `layouts/app.blade.php` tombol lonceng
tidak memiliki `id` khusus. JS harus menggunakan selector menyesuaikan:

```javascript
const btnNotif = document.querySelector('.topbar-icon-btn[aria-label="Notifikasi"]')
              || document.querySelector('.topbar-right .topbar-icon-btn');
```

**Posisi & tampilan:**
- Dropdown panel overlay di bawah ikon lonceng, pojok kanan atas
- Lebar: `360px` di desktop, `calc(100vw - 16px)` di mobile (≤640px)
- Backdrop transparan, klik di luar menutup panel
- Tampilkan **4 notifikasi terbaru**
- Setiap item: ikon jenis, judul, waktu relatif, dot indikator jika belum dibaca,
  background lebih terang untuk notif belum dibaca

**Aksi:**
- Tombol **"Tandai semua dibaca"** → `PATCH /api/notifikasi/baca-semua` → refresh
- Klik item → tandai dibaca (fire-and-forget) → redirect ke URL tujuan
- Link **"Lihat semua"** → navigasi ke `/hr/notifikasi`

**Loading state:** Skeleton loader 3 baris.

**Empty state:** Teks "Tidak ada notifikasi" + ikon lonceng kecil.

### B. Badge Count

- Elemen badge: `<span class="topbar-notif-dot">` di topbar `layouts/app.blade.php`
- Tambahkan juga `id="notif-dot-hr"` pada badge ini via markup kondisional agar tidak
  konflik dengan badge milik User Departemen yang menggunakan id yang sama.

  **Strategi:** Gunakan class selector, bukan id, untuk menghindari konflik:
  ```javascript
  // Gunakan class selector yang sudah ada, bukan id
  const dot = document.querySelector('.topbar-notif-dot');
  ```

- `jumlah_belum_dibaca > 0` → tampilkan badge
- `jumlah_belum_dibaca === 0` → sembunyikan badge
- **Polling** setiap 30 detik

### C. Halaman Penuh (`/hr/notifikasi`)

**Layout:** `@extends('layouts.app')` dengan sidebar HR.

**Fitur:**
- Paginasi 25 per halaman
- Filter tab: **"Semua"** | **"Belum Dibaca"** | **"Sudah Dibaca"**
- Tombol "Tandai semua dibaca"
- Klik item → tandai dibaca → redirect
- Paginasi: Prev / Next + "Halaman X dari Y"

---

## Mapping Jenis Notifikasi → URL Tujuan (Role HR)

```javascript
function getNotifikasiUrl(jenis, id_referensi) {
  const map = {
    'izin'    : '/hr/dokumen',
    'absensi' : '/hr/dashboard',
    'lembur'  : '/hr/dashboard',
    'planning': '/hr/dashboard',
    'sistem'  : '/hr/dashboard',
  };
  return map[jenis] || '/hr/dashboard';
}
```

---

## Mapping Jenis Notifikasi → Ikon

| `jenis` | Ikon (Heroicons outline) | Warna aksen |
|---|---|---|
| `izin` | `document-text` | `var(--hr-green-600, #16a34a)` |
| `absensi` | `check-circle` | `var(--hr-green-500, #22c55e)` |
| `lembur` | `clock` | `#7c3aed` |
| `planning` | `calendar` | `var(--hr-green-700, #15803d)` |
| `sistem` | `information-circle` | `var(--text-muted)` |
| *(default)* | `bell` | `var(--text-muted)` |

> Warna aksen menggunakan green tone karena color scheme HR adalah hijau/teal
> (sesuai `hr.css` yang sudah ada — class `hr-stat-card--green`, dll.).

---

## Spesifikasi Markup Panel Overlay

Tambahkan ke `layouts/app.blade.php` tepat sebelum `</body>`.

**Jika prompt User Departemen sudah dijalankan sebelumnya**, kondisi yang ada:
```blade
@if(auth()->check() && auth()->user()->role === 'user_departemen')
```
harus diperluas menjadi:
```blade
@if(auth()->check() && in_array(auth()->user()->role, ['user_departemen', 'hr']))
```
Markup panel **tidak perlu diduplikasi** — satu markup cukup untuk kedua role karena
strukturnya identik. Hanya class CSS dan JS yang berbeda per file.

**Jika prompt User Departemen belum dijalankan**, tambahkan markup baru:

```html
@if(auth()->check() && in_array(auth()->user()->role, ['user_departemen', 'hr']))
<div id="notif-panel-overlay" aria-hidden="true">
  <div id="notif-backdrop"></div>
  <div id="notif-panel" role="dialog" aria-label="Notifikasi" aria-modal="false">

    <div class="app-notif-panel-header">
      <span class="app-notif-panel-title">Notifikasi</span>
      <div class="app-notif-panel-actions">
        <button id="btn-tandai-semua-baca" type="button">
          Tandai semua dibaca
        </button>
        <button id="btn-tutup-notif-panel" type="button" aria-label="Tutup panel notifikasi">
          <!-- icon X SVG -->
        </button>
      </div>
    </div>

    <div id="notif-panel-list" role="list">
      <!-- diisi JS -->
    </div>

    <div class="app-notif-panel-footer">
      <!-- URL diisi oleh JS berdasarkan role yang login -->
      <a href="#" id="notif-see-all-link" class="app-notif-panel-see-all">
        Lihat semua notifikasi
      </a>
    </div>

  </div>
</div>
@endif
```

> Perhatikan bahwa jika markup dibagikan antar role (User Departemen dan HR), link
> "Lihat semua" harus diisi dinamis oleh JS berdasarkan role:
> ```javascript
> // Di init(), set URL "Lihat semua" sesuai role
> const seeAllLink = document.getElementById('notif-see-all-link');
> if (seeAllLink) {
>   // Deteksi role dari URL atau dari data attribute di body
>   const isHR = window.location.pathname.startsWith('/hr');
>   seeAllLink.href = isHR ? '/hr/notifikasi' : '/departemen/notifikasi';
> }
> ```

---

## Spesifikasi CSS (`resources/css/hr.css`)

Tambahkan di bagian paling bawah. **Jangan ubah CSS yang sudah ada.**

Jika `app-notif-*` CSS sudah ditambahkan oleh prompt User Departemen di `departemen.css`,
HR CSS di `hr.css` hanya perlu menambahkan **override warna** yang berbeda:

```css
/* Override warna aksen notifikasi untuk HR (green scheme) */
.app-notif-item--unread {
  background: color-mix(in srgb, #f0fdf4 60%, white);
}
.app-notif-item--unread:hover {
  background: #dcfce7;
}
.app-notif-page-tab--active {
  background: #f0fdf4;
  color: #15803d;
}
```

Jika prompt User Departemen **belum** dijalankan, tambahkan CSS lengkap:

```
#notif-panel-overlay             → position: fixed; inset: 0; z-index: 9500; pointer-events: none
#notif-panel-overlay.app-notif--open → pointer-events: auto
#notif-backdrop                  → position: absolute; inset: 0; background: rgba(0,0,0,0.3)
                                     opacity: 0; transition: opacity 0.2s
#notif-panel-overlay.app-notif--open #notif-backdrop → opacity: 1
#notif-panel                     → position: absolute; top: calc(topbar height + 8px); right: 16px
                                     width: 360px; max-height: 520px
                                     background: var(--surface-card); border-radius: var(--radius-xl)
                                     box-shadow: 0 8px 32px rgba(0,0,0,0.12)
                                     transform: translateY(-8px) scale(0.97); opacity: 0
                                     transition: transform 0.25s, opacity 0.2s
                                     display: flex; flex-direction: column; overflow: hidden
#notif-panel-overlay.app-notif--open #notif-panel → transform: translateY(0) scale(1); opacity: 1

.app-notif-item                  → display: flex; gap: var(--space-3)
                                     padding: var(--space-3) var(--space-4)
                                     cursor: pointer; transition: background 0.15s
                                     border-bottom: 1px solid var(--surface-border)
.app-notif-item:hover            → background: var(--surface-bg)
.app-notif-item--unread          → background: color-mix(in srgb, #f0fdf4 60%, white)
.app-notif-item--unread:hover    → background: #dcfce7

.app-notif-page-tabs             → display: flex; gap: 4px
                                     padding: var(--space-3) var(--space-4)
                                     border-bottom: 1px solid var(--surface-border)
.app-notif-page-tab              → padding: 6px 16px; border-radius: var(--radius-md)
                                     font-size: 13px; font-weight: 500; cursor: pointer
                                     border: none; background: none; color: var(--text-secondary)
.app-notif-page-tab--active      → background: #f0fdf4; color: #15803d

@media (max-width: 640px)
  #notif-panel                   → width: calc(100vw - 16px); right: 8px
```

---

## Spesifikasi JS (`resources/js/hr/notifikasi.js`)

### Struktur modul

```javascript
// 1. State
// 2. init()
// 3. fetchBadgeCount()
// 4. fetchPanelNotifikasi()
// 5. renderPanelItems(items)
// 6. openPanel() / closePanel()
// 7. tandaiBaca(id)
// 8. tandaiSemuaBaca()
// 9. getNotifikasiUrl(jenis, id_referensi)
// 10. getIkonSvg(jenis)
// 11. formatWaktuRelatif(isoString)
// 12. startPolling()
// -- Jika halaman ini adalah /hr/notifikasi --
// 13. initHalamanPenuh()
// 14. fetchHalamanPenuh(page, filter)
// 15. renderHalamanPenuh(data)
// 16. renderPaginasiHalaman(meta)
```

### Selector & Deteksi Role

```javascript
function init() {
  // Tombol lonceng tidak punya id di layouts/app.blade.php
  const btnNotif = document.querySelector('.topbar-icon-btn[aria-label="Notifikasi"]')
                || document.querySelector('.topbar-right .topbar-icon-btn');

  if (!btnNotif) return;

  // Set URL "Lihat semua" berdasarkan deteksi path
  const seeAllLink = document.getElementById('notif-see-all-link');
  if (seeAllLink) seeAllLink.href = '/hr/notifikasi';

  btnNotif.addEventListener('click', openPanel);
  document.getElementById('btn-tutup-notif-panel')?.addEventListener('click', closePanel);
  document.getElementById('notif-backdrop')?.addEventListener('click', closePanel);
  document.getElementById('btn-tandai-semua-baca')?.addEventListener('click', tandaiSemuaBaca);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePanel(); });

  fetchBadgeCount();
  startPolling();

  if (window.location.pathname === '/hr/notifikasi') initHalamanPenuh();
}
```

### Deteksi class panel (menyesuaikan dengan User Departemen)

```javascript
// Gunakan class yang sama dengan User Departemen agar markup panel bisa dibagikan
const PANEL_OPEN_CLASS = 'app-notif--open'; // atau 'd-notif--open' jika User Dept sudah pakai itu
// Sesuaikan dengan class yang diputuskan saat mengerjakan prompt User Departemen
```

> **Catatan untuk AI Agent:** Cek terlebih dahulu class open state yang sudah digunakan
> di `departemen.css` / `user-departemen/notifikasi.js`. Gunakan class yang sama agar
> panel bisa di-toggle oleh kedua file JS tanpa konflik.

### `formatWaktuRelatif(isoString)`

Implementasikan tanpa library:
- < 1 menit → "Baru saja"
- < 60 menit → "X menit yang lalu"
- < 24 jam → "X jam yang lalu"
- < 7 hari → "X hari yang lalu"
- >= 7 hari → "DD MMM YYYY" Bahasa Indonesia

---

## Spesifikasi `hr/notifikasi.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Notifikasi')
@section('breadcrumb-parent', 'HR Ecogreen')
@section('breadcrumb-current', 'Notifikasi')
@section('sidebar-role', 'HR Ecogreen')

@section('sidebar-nav')
    {{-- Salin sidebar nav yang sama persis dari hr/dashboard.blade.php --}}
    <div class="nav-section-label">Beranda</div>
    <a href="{{ url('/hr/dashboard') }}"
       class="nav-item {{ request()->is('hr/dashboard') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
            </svg>
        </span>
        <span class="nav-item-label">Dashboard</span>
    </a>

    <div class="nav-section-label">Verifikasi</div>
    <a href="{{ url('/hr/dokumen') }}"
       class="nav-item {{ request()->is('hr/dokumen*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
        </span>
        <span class="nav-item-label">Verifikasi Dokumen</span>
    </a>

    <div class="nav-section-label">Rekap & Laporan</div>
    <a href="{{ url('/hr/rekap') }}"
       class="nav-item {{ request()->is('hr/rekap*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
        </span>
        <span class="nav-item-label">Rekap Absensi</span>
    </a>

    <div class="nav-section-label">Sistem</div>
    <a href="{{ url('/hr/audit') }}"
       class="nav-item {{ request()->is('hr/audit*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/>
            </svg>
        </span>
        <span class="nav-item-label">Audit Log</span>
    </a>

    {{-- Notifikasi --}}
    <div class="nav-section-label">Lainnya</div>
    <a href="{{ url('/hr/notifikasi') }}"
       class="nav-item nav-item--active">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
            </svg>
        </span>
        <span class="nav-item-label">Notifikasi</span>
    </a>
@endsection

@push('styles')
    @vite('resources/css/hr.css')
@endpush

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-subtitle">Semua notifikasi dan pembaruan aktivitas Anda.</p>
        </div>
        <button id="btn-tandai-semua-halaman" class="hr-btn-outline">
            Tandai semua dibaca
        </button>
    </div>

    <!-- Tab Filter -->
    <div class="app-notif-page-tabs" role="tablist">
        <button class="app-notif-page-tab app-notif-page-tab--active"
                data-filter="" role="tab">Semua</button>
        <button class="app-notif-page-tab"
                data-filter="false" role="tab">Belum Dibaca</button>
        <button class="app-notif-page-tab"
                data-filter="true" role="tab">Sudah Dibaca</button>
    </div>

    <!-- List -->
    <div class="dash-panel dash-panel--full">
        <div id="notif-halaman-list" class="dash-panel-body">
            @for ($i = 0; $i < 5; $i++)
                <div style="display:flex;gap:12px;padding:12px 20px;">
                    <div class="skeleton-line"
                         style="width:36px;height:36px;flex-shrink:0;border-radius:8px;">
                    </div>
                    <div style="flex:1;">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line skeleton-line--medium"></div>
                    </div>
                </div>
            @endfor
        </div>
        <div id="paginasi-notif-halaman" style="padding:12px 20px;"></div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/hr/notifikasi.js'])
@endpush
```

---

## Modifikasi Sidebar Nav di Semua Blade HR

Tambahkan item menu "Notifikasi" di setiap file yang memiliki `@section('sidebar-nav')`:
`dashboard.blade.php`, `dokumen.blade.php`, `rekap.blade.php`, `rekap-detail.blade.php`, `audit.blade.php`.

Tambahkan di bagian paling bawah dari masing-masing `@section('sidebar-nav')`:

```blade
<div class="nav-section-label">Lainnya</div>
<a href="{{ url('/hr/notifikasi') }}"
   class="nav-item {{ request()->is('hr/notifikasi*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
        </svg>
    </span>
    <span class="nav-item-label">Notifikasi</span>
    <span class="nav-badge" id="sidebar-notif-badge-hr" style="display:none;">0</span>
</a>
```

Update `fetchBadgeCount()` di `hr/notifikasi.js` agar juga memperbarui
`#sidebar-notif-badge-hr`.

---

## Modifikasi `routes/web.php`

Tambahkan di dalam grup `role:hr`:

```php
Route::get('notifikasi', [HRPageController::class, 'notifikasi'])->name('notifikasi');
```

---

## Modifikasi `app/Http/Controllers/HR/PageController.php`

Tambahkan method:

```php
/** Halaman semua notifikasi HR */
public function notifikasi(): View
{
    return view('hr.notifikasi');
}
```

---

## Modifikasi `vite.config.js`

Tambahkan ke array `input` di bagian JS HR:

```javascript
'resources/js/hr/notifikasi.js',
```

---

## Aturan Wajib

1. **Jangan ubah** CSS yang sudah ada di `hr.css` — hanya tambah di bagian bawah
2. Markup panel di `layouts/app.blade.php` **wajib kondisional** — perluas kondisi yang
   sudah ada untuk User Departemen, jangan duplikasi markup
3. Gunakan class `app-notif-*` (bukan `d-notif-*` yang khusus User Departemen) agar
   markup panel bisa digunakan bersama
4. JS harus **defensif** — semua `document.getElementById` dan `querySelector` harus dicek
   null sebelum digunakan
5. Semua AJAX wajib menyertakan `X-CSRF-TOKEN` via `$.ajaxSetup`
6. Gunakan **jQuery (`$`)** — sudah tersedia via `bootstrap.js`
7. Panel harus bisa ditutup: klik backdrop, klik tombol tutup, tekan `Escape`
8. Saat redirect setelah klik notif — fire-and-forget `tandaiBaca()`, langsung redirect
9. Link "Lihat semua" di panel harus mengarah ke `/hr/notifikasi` (diset via JS, bukan hardcode di HTML)
10. Seluruh teks UI dalam **Bahasa Indonesia**
